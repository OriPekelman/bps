package edu.berkeley.bps.services.workspace.collapser;

import edu.berkeley.bps.services.workspace.Entity;
import edu.berkeley.bps.services.workspace.Person;

public class UnqualifiedCompatibleNameShiftRule extends CollapserRuleBaseWithUI {
	private static final String myClass = "UnqualifiedCompatibleNameShiftRule";

	public UnqualifiedCompatibleNameShiftRule(double weight, boolean intraDocument) {
		super(SHIFT_RULE, myClass, weight, intraDocument);
	}

	@Override
	public double evaluate(Entity fromEntity, Entity toEntity) {
		if(!(fromEntity instanceof Person) || 
			!(toEntity instanceof Person))
			throw new IllegalArgumentException(myClass+".evaluate must take Persons!");
		Person fromPerson = (Person)fromEntity;
		Person toPerson = (Person)toEntity;
		// Consider shifting from into to
		if(fromPerson.isUnfiliated() && 
				(toPerson.isFullyFiliated() || toPerson.isPartiallyFiliated())
			&& Person.COMPAT_MORE_INFO==toPerson.compareByNames(fromPerson)) {
				return weight;
		// Consider shifting to into from
		} else if(toPerson.isUnfiliated() && 
				(fromPerson.isFullyFiliated() || fromPerson.isPartiallyFiliated())
			&& Person.COMPAT_MORE_INFO==fromPerson.compareByNames(toPerson)) {
			return -weight;	// invert shift direction
		} else { // not equal, so this rule does not apply
			return SHIFT_RULE_NO_MATCH;
		}
	}

}