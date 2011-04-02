{include file="header.tpl"}

	<p class="nav-right">
	{if isset($corpusID) }
		<a href="/corpora/corpus?id={$corpusID}">Return to Corpus details</a>
		</p>
		<h1>Corpus Document Details</h1>
	{elseif isset($workspaceID) }
		<a href="/workspace?id={$workspaceID}">Return to Workspace details</a>
		</p>
		<h1>Workspace Document Details</h1>
	{/if}

{if isset($errmsg) }
	<h2>{$errmsg}</h2>
{else}
	{if !isset($document) }
		<p>No document specified!</p>
	{else}
		<table class="docs_row" border="0" cellspacing="0" cellpadding="4px" width="100%">
			<tr>
				<td class="title" width="200px">Document</td>
				<td class="title" width="200px">Publication</td>
				<td class="title" width="400px">Notes</td>
				<td class="title" width="100px">Date</td>
			</tr>
			<tr>
				<td class="document" style="padding-top:6px">{$document.alt_id}</td>
				<td class="document" style="padding-top:6px">&nbsp; </td>
				<td class="document" style="padding-top:6px">{$document.notes}</td>
				<td class="document" style="padding-top:6px">{$document.date_str}</td>
			</tr>
		</table>
		<div class="nrads_row">
			{if empty($nrads)}
			<h2><i>No</i> Name-Role-Activity instances found in Document</h2>
			{else}
			<h2>{$nrads|@count} Name-Role-Activity instances in Document:</h2>
			<table class="nrads_row" border="0" cellspacing="0" cellpadding="4px" width="100%">
				<tr>
					<td class="title" width="200px">Name</td>
					<td class="title" width="200px">Normalized Form</td>
					<td class="title" width="140px">Role</td>
					<td class="title" width="140px">Activity</td>
					<td class="title" width="140px">XML ID</td>
				</tr>
				{section name=nrad loop=$nrads}
					<tr>
						<td class="nrad" style="padding-top:6px">
							{if $nrads[nrad].activityRoleIsFamily}
								<span class="familyIndent">&nbsp;</span>
							{/if}
							{$nrads[nrad].name}
						</td>
						<td class="nrad" style="padding-top:6px">
							{if $nrads[nrad].normalNameId != $nrads[nrad].nameId}
								{$nrads[nrad].normalName}
							{else}
							&nbsp;-&nbsp; 
							{/if}
						</td>
						<td class="nrad" style="padding-top:6px">
							{if $nrads[nrad].activityRoleIsFamily}
								<span class="familyIndent">&nbsp;</span>
							{/if}
							{$nrads[nrad].activityRole}
						</td>
						<td class="nrad" style="padding-top:6px">
							{$nrads[nrad].activity}
						</td>
						<td class="nrad" style="padding-top:6px">
							{$nrads[nrad].xmlId}
						</td>
					</tr>
						{if isset($nrads[nrad].links)}
							<tr>
								{section name=ilink loop=$nrads[nrad].links}
									<td class="nrad" colspan="3">
										{if $nrads[nrad].activityRoleIsFamily}
											<span class="familyIndent">&nbsp;</span>
										{/if}
										<span class="nradLink">-- {$nrads[nrad].links[ilink].linkTo}&nbsp;
											<span class="nradLinkWeight">({$nrads[nrad].links[ilink].weight}%)</span>
										</span>
									</td>
								{/section}
							</tr>
						{elseif isset($workspaceID)}
							<tr>
								<td class="nrad" colspan="3">
									{if $nrads[nrad].activityRoleIsFamily}
										<span class="familyIndent">&nbsp;</span>
									{/if}
									<span class="nradLink">-- (no Person/Clan associations computed)</span>
								</td>
							</tr>
						{/if}
				{/section}
			</table>
			{/if}
		</div>
	{/if}
	<p>&nbsp;</p>
{/if}
	<p class="nav-right">
	{if isset($corpusID) }
		<a href="/corpora/corpus?id={$corpusID}">Return to Corpus details</a>
	{elseif isset($workspaceID) }
		<a href="/workspace?id={$workspaceID}">Return to Workspace details</a>
	{/if}
	</p>

{include file="footer.tpl"}
