<div class="generic-content-wrapper">
	<div class="section-title-wrapper">
		<script>
			function primehub(id) {
				$.post(baseurl + '/locs','primary='+id,function(data) { window.location.href=window.location.href; });
			}
			function drophub(id) {
				$.post(baseurl + '/locs','drop='+id,function(data) { window.location.href=window.location.href; });
			}
		</script>
		<button class="btn btn-success btn-xs pull-right" onclick="window.location.href='/locs/f=&sync=1'; return false;"><i class="icon-refresh"></i>&nbsp;{{$sync}}</button>
		<h2>{{$header}}</h2>
	</div>
	<div class="section-content-wrapper-np">
		<div class="section-content-warning-wrapper">
			{{$sync_text}}
		</div>
		<div class="section-content-info-wrapper">
			{{$drop_text}}<br>
			{{$last_resort}}
		</div>
		<table id="locs-index">
			<tr>
				<th>{{$loc}}</th>
				<th>{{$addr}}</th>
				<th>{{$mkprm}}</th>
				<th>{{$drop}}</th>
			</tr>
			{{foreach $hubs as $hub}}
			{{if ! $hub.deleted }}
			<tr>
				<td>{{$hub.hubloc_url}}</td>
				<td>{{$hub.hubloc_addr}}</td>
				<td>{{if $hub.primary}}<i class="icon-check"></i>{{else}}<i class="icon-check-empty primehub" onclick="primehub({{$hub.hubloc_id}}); return false;"></i>{{/if}}</td>
				<td><i class="icon-trash drophub" onclick="drophub({{$hub.hubloc_id}}); return false;"></i></td>
			</tr>
			{{/if}}
			{{/foreach}}
		</table>
	</div>
</div>
