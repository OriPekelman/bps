package edu.berkeley.bps.services.workspace.collapser;

import java.util.Collections;
import java.util.Comparator;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

import javax.management.RuntimeErrorException;

import edu.berkeley.bps.services.common.LinkType;
import edu.berkeley.bps.services.corpus.NameRoleActivity;
import edu.berkeley.bps.services.workspace.Entity;
import edu.berkeley.bps.services.workspace.EntityLinkSet;
import edu.berkeley.bps.services.workspace.NRADEntityLink;
import edu.berkeley.bps.services.workspace.Person;

public class PersonCollapser extends CollapserBase implements Collapser {
	private final static String myClass = "PersonCollapser";

	public static class EntitySortByNQuals implements Comparator<Entity> {
		public int compare(Entity p1, Entity p2) {
			// Note that we invert the normal subtraction direction to 
			// get a descending sort.
			return p2.getNumQualifiers()-p1.getNumQualifiers();
		}
	}
	
	/**
	 * Evaluates collapsing (shifting weight) among the entities.
	 * Evaluates collapsing (shifting weight) among the entities.
	 * @param entities The list of entities that are being compared to one another
	 * @param nradToEntityLinks Map indexed by nrad, of the sets of weights 
	 * 				to persons or clans
	 * @param personTopersonToEntityLinkSets Map indexed by personId, of lists of 
	 * 				EntityLinkSets for the NRADs that point to this person
	 * @param intraDocument true if evaluating pairs within a document, 
	 * 				false if this is corpora-wide
	 */
	@Override
	public void evaluateList(List<? extends Entity> entities, 
			HashMap<Integer, EntityLinkSet<NameRoleActivity>> nradToEntityLinks, 
			HashMap<Person, List<EntityLinkSet<NameRoleActivity>>> personToEntityLinkSets,
			boolean intraDocument) {
		if(entities==null || entities.isEmpty())
			throw new IllegalArgumentException("No Persons to collapse");
		if(nradToEntityLinks==null || nradToEntityLinks.isEmpty())
			throw new IllegalArgumentException("Missing/invalid entityToNRADLinks map");

		// First, sort the list by #qualifications, descending
		Collections.sort(entities, new EntitySortByNQuals());
		
		try {
			int nPersons = entities.size();
			for(int iToPers=0; iToPers<nPersons;iToPers++) {
				Person toPerson = (Person)entities.get(iToPers);
				// Consider this Person against all the following ones.
				for(int iFromPers=iToPers+1; iFromPers<nPersons;iFromPers++) {
					Person fromPerson = (Person)entities.get(iFromPers);
					// Now, we run through the rules. 
					// First try the shift rules, taking the
					// first match we get - we should only get one.
					double totalShift = 0;
					List<CollapserRule> ruleList = 
						getRules(CollapserRule.SHIFT_RULE, intraDocument);
					for(CollapserRule rule:ruleList) {
						double shift = rule.evaluate(fromPerson, toPerson);
						if(shift!=0) {	// we have a match. Can go either direction.
							if(shift<-1 || shift>1)
								throw new RuntimeException(
										"Shift CollapserRule "+rule.getName()
										+"returned value out of range:"+shift);
							totalShift = shift;
							break;
						}
					}
					if(totalShift!=0) {	// any match in the set?
						// normalize the shift direction for simplicity
						if(totalShift<0) {
							Person temp = fromPerson;
							fromPerson = toPerson;
							toPerson = temp;
							totalShift = -totalShift;
						}
						// Look for matching discount rules, using all that match
						ruleList = 
							getRules(CollapserRule.DISCOUNT_RULE, intraDocument);
						for(CollapserRule rule:ruleList) {
							double discount = rule.evaluate(fromPerson, toPerson);
							if(discount==0) {	// we have a match
								totalShift = 0;
								break;			// We can stop here
							} else if(discount>0) {		// match
								if(discount>1)
									throw new RuntimeException(
											"Discount CollapserRule "+rule.getName()
											+"returned value out of range:"+discount);
								totalShift *= discount;
							} // if < 0, indicates no match
						}
						if(totalShift!=0) { // Any shift left?
							// Look for matching boost rules, using all that match
							ruleList = 
								getRules(CollapserRule.BOOST_RULE, intraDocument);
							for(CollapserRule rule:ruleList) {
								double boost = rule.evaluate(fromPerson, toPerson);
								if(boost > 1) {	// we have a match
									// Apply the boost, but max out at 1
									totalShift = Math.min(1, totalShift*boost);
								} else if(boost<1) { // bad value
									throw new RuntimeException(
												"Boost CollapserRule "+rule.getName()
												+"returned value out of range:"+boost);
								} // if == 1, indicates no match or no action
							}
						}
					}
					if(totalShift!=0) {	// any shift to do?
						// Perform the shift on the nrad links
						handleShift( fromPerson, toPerson, totalShift,
								nradToEntityLinks, 
								personToEntityLinkSets );
					}
				}
			}
		} catch(ClassCastException cce) {
			throw new RuntimeException(
				"Apparent attempt to use PersonCollapser with non-Person entities."+cce);
		}
	}
	
	protected void handleShift( Person fromPerson, Person toPerson, double shift,
			HashMap<Integer, EntityLinkSet<NameRoleActivity>> nradToEntityLinks, 
			HashMap<Person, List<EntityLinkSet<NameRoleActivity>>> personToEntityLinkSets ) {
		// We run through all the NRADs that point to fromPerson,
		// reduce their current weight by 1-shift (multiply by 1-shift).
		// Then for each of those NRADs:
		//   If toPerson does not already have a link from the same NRAD,
		//   the first create a link and insert it into the LinkSet for toPerson.
		//   Increase (or set, if new) the weight on the NRAD to toPerson link.
		// Then normalize the linkSets for both fromPerson and toPerson
		if(shift<=0)
			throw new IllegalArgumentException(
					"Shift value for PersonCollapser.handleShift <= 0: "+shift);
		List<EntityLinkSet<NameRoleActivity>> linkSetsForFromPerson = 
			personToEntityLinkSets.get(fromPerson);
		if(linkSetsForFromPerson==null||linkSetsForFromPerson.isEmpty()) {
			throw new RuntimeException(myClass+
					".handleShift: linkSets for fromPerson is null or emtpy");
		}
		List<EntityLinkSet<NameRoleActivity>> linkSetsForToPerson = 
			personToEntityLinkSets.get(toPerson);
		if(linkSetsForToPerson==null||linkSetsForToPerson.isEmpty()) {
			throw new RuntimeException(myClass+
					".handleShift: linkSets for toPerson is null or emtpy");
		}
		// For each set that includes fromPerson
		for(EntityLinkSet<NameRoleActivity> linkSet:linkSetsForFromPerson) {
			// scale the link to the fromPerson - returns the weight shifted.
			double delta = linkSet.scaleLink(fromPerson, shift);
			// Now we shift that to the toPerson. If toPerson is in linkSet, 
			// just adjust it. Otherwise, create a new link.
			NRADEntityLink link = (NRADEntityLink)linkSet.get(toPerson);
			if(link!=null) {
				linkSet.adjustLink(toPerson, delta);
			} else {
				link = new NRADEntityLink(linkSet.getFromObj(), toPerson, delta,
										LinkType.Type.LINK_TO_PERSON);
				linkSet.put(toPerson, link);
				// We need to add this linkSet to the List for toPerson 
				linkSetsForToPerson.add(linkSet);
			}
		}
		
	}

}
