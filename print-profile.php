<!doctype html>
<html lang="en" ng-app="print" ng-controller="print">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
		<meta http-equiv="X-UA-Compatible" content="IE=edge" />
		<title>Printing Profiles</title>
		<link href="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.1.1/css/bootstrap.css" rel="stylesheet">
		<link href="css.css" rel="stylesheet">
		<style type="text/css">
			table { page-break-inside:auto }
			tr    { page-break-inside:avoid; page-break-after:auto }
			thead { display:table-header-group }
			tfoot { display:table-footer-group }
		</style>
	</head>
	<body>
		<div class="container">
			<div class="form-group row hidden-print" style="margin-top:20px">
				<div class="col-xs-2 hidden-xs">
					<div class="btn-group">
						<a type="button" class="btn btn-default" ng-class="{active:view=='tile'}" ng-click="view='tile'">
							<span class="glyphicon glyphicon-th-large"></span>
						</a>
						<a type="button" class="btn btn-default" ng-class="{active:view=='list'}" ng-click="view='list'">
							<span class="glyphicon glyphicon-list"></span>
						</a>
					</div>
				</div>

				<div class="col-xs-5 col-xs-offset-2">
					<div class="input-group">
						<label class="input-group-addon" for="myEvent">Event: </label>
						<select class="form-control" ng-model="myEvent" id="myEvent" 
							ng-options="c.conferenceID as c.title group by c.start_year for c in confs | orderBy:['-start_year', 'title']"
						>
							<option value="">&mdash; all events &mdash;</option>
						</select>
					</div>
				</div>

				<div class="hidden-xs col-xs-2 col-xs-offset-1" ng-hide="filtered.length < 8">
					<button onclick="window.print();" class="btn btn-success">Print</button>
				</div>
			</div>

			<div data-ng-switch="view">
				<div data-ng-switch-when="tile">
					<div class="row">
						<span class="col-xs-3" data-ng-repeat="user in (filtered_users = (users | isAttending:myEvent | hasImg | filter:search_str)) | orderBy:['name'] ">
							<div class="thumbnail" data-ng-click="show_me(user)">
								<img data-ng-src="http://upstreamacademy.com/apps/{{user.photo || '000-blank.jpg'}}" class="img-rounded" height="100" 
									data-ng-attr-title="{{user.name}}"
									data-ng-attr-alt="{{user.name}}" alt="John Doe"/>
							</div>
						</span>
					</div>
					<div class="col-xs-6 col-xs-offset-3" ng-show="filtered_users.length == 0">
						<div class="well">
							<p>It appears there are no attendees to this event that have a profile photo. Please change to list view (<small class="glyphicon glyphicon-list"></small>) to see the event attendees.</p>
						</div>
					</div>
				</div>
				<div data-ng-switch-when="list">
					<table class="table" data-ng-cloak>
						<thead>
							<tr>
								<th>Img</th>
								<th>Contact</th>
								<th>Bio</th>
							</tr>
						</thead>
						<tbody>
							<tr data-ng-repeat="user in (filtered_users = (users | isAttending:myEvent | filter:search_str)) | orderBy:['name'] " ng-dblclick="edit_user(user)" >
								<td>
									<div class="center-cropped pull-left img-rounded">
										<img data-ng-src="http://upstreamacademy.com/apps/{{user.photo || '000-blank.jpg'}}" class="img-rounded" height="100" 
										data-ng-attr-title="{{user.name}}"
										data-ng-attr-alt="{{user.name}}" alt="John Doe" />
									</div>
								</td>
								<td>
									<!-- <div class="pull-right">
										<a data-ng-href="tel:{{user.phone}}" data-ng-show="user.phone"><i class="glyphicon glyphicon-phone-alt"></i></a>
										<a data-ng-href="mailto:{{user.email}}" data-ng-show="user.email"><i class="glyphicon glyphicon-envelope"></i></a>
									</div> -->
									<strong>
										<span data-ng-bind="user.name">John Doe</span>&nbsp;
										<i class="glyphicon glyphicon-comment" data-ng-show="user.note"></i>
									</strong><br/>
									<small data-ng-bind="user.title">Intern</small><br/>
									<span class="text-muted">
										<small data-ng-bind="user.firm">Temporary INC.</small><br/>
										<small data-ng-bind="user.city">Two Dot</small>,&nbsp;<small data-ng-bind="user.state">MT</small>
									</span>
								</td>
								<td>
									<span ng-show="user.bio">{{user.name}} {{user.bio}}</span>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		
		<script src="//cdnjs.cloudflare.com/ajax/libs/angular.js/1.2.14/angular.min.js"></script>
		<script src="js/js.js"></script>
		<script src="js/print.js"></script>
	</body>
</html>