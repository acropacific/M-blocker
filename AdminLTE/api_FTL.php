<?php
/*   Pi-hole: A black hole for Internet advertisements
*    (c) 2017 Pi-hole, LLC (https://pi-hole.net)
*    Network-wide ad blocking via your own hardware.
*
*    This file is copyright under the latest version of the EUPL.
*    Please see LICENSE file for your rights under this license */


if(!isset($api))
{
	die("Direct call to api_FTL.php is not allowed!");
}

$socket = connectFTL("127.0.0.1");

if (isset($_GET['type'])) {
	$data["type"] = "FTL";
}

if (isset($_GET['version'])) {
	$data["version"] = 3;
}

if (isset($_GET['summary']) || isset($_GET['summaryRaw']) || !count($_GET))
{
	sendRequestFTL("stats");
	$return = getResponseFTL();

	$stats = [];
	foreach($return as $line)
	{
		$tmp = explode(" ",$line);

		if(isset($_GET['summary']))
		{
			if($tmp[0] !== "ads_percentage_today")
			{
				$stats[$tmp[0]] = number_format($tmp[1]);
			}
			else
			{
				$stats[$tmp[0]] = number_format($tmp[1], 1, '.', '');
			}
		}
		else
		{
			$stats[$tmp[0]] = intval($tmp[1]);
		}
	}
	$data = array_merge($data,$stats);
}

if (isset($_GET['overTimeData10mins']))
{
	sendRequestFTL("overTime");
	$return = getResponseFTL();

	$domains_over_time = array();
	$ads_over_time = array();
	foreach($return as $line)
	{
		$tmp = explode(" ",$line);
		$domains_over_time[intval($tmp[0])] = intval($tmp[1]);
		$ads_over_time[intval($tmp[0])] = intval($tmp[2]);
	}
	$result = array('domains_over_time' => $domains_over_time,
	                'ads_over_time' => $ads_over_time);
	$data = array_merge($data, $result);
}

if (isset($_GET['topItems']) && $auth)
{
	if(is_numeric($_GET['topItems']))
	{
		sendRequestFTL("top-domains (".$_GET['topItems'].")");
	}
	else
	{
		sendRequestFTL("top-domains");
	}

	$return = getResponseFTL();
	$top_queries = array();
	foreach($return as $line)
	{
		$tmp = explode(" ",$line);
		$top_queries[$tmp[2]] = intval($tmp[1]);
	}

	if(is_numeric($_GET['topItems']))
	{
		sendRequestFTL("top-ads (".$_GET['topItems'].")");
	}
	else
	{
		sendRequestFTL("top-ads");
	}

	$return = getResponseFTL();
	$top_ads = array();
	foreach($return as $line)
	{
		$tmp = explode(" ",$line);
		$top_ads[$tmp[2]] = intval($tmp[1]);
	}

	$result = array('top_queries' => $top_queries,
	                'top_ads' => $top_ads);

	$data = array_merge($data, $result);
}

if ((isset($_GET['topClients']) || isset($_GET['getQuerySources'])) && $auth)
{

	if(isset($_GET['topClients']))
	{
		$number = $_GET['topClients'];
	}
	elseif(isset($_GET['getQuerySources']))
	{
		$number = $_GET['getQuerySources'];
	}

	if(is_numeric($number))
	{
		sendRequestFTL("top-clients (".$number.")");
	}
	else
	{
		sendRequestFTL("top-clients");
	}

	$return = getResponseFTL();
	$top_clients = array();
	foreach($return as $line)
	{
		$tmp = explode(" ",$line);
		if(count($tmp) == 4)
		{
			$top_clients[$tmp[3]."|".$tmp[2]] = intval($tmp[1]);
		}
		else
		{
			$top_clients[$tmp[2]] = intval($tmp[1]);
		}
	}

	$result = array('top_sources' => $top_clients);
	$data = array_merge($data, $result);
}

if (isset($_GET['getForwardDestinations']) && $auth)
{
	sendRequestFTL("forward-dest");
	$return = getResponseFTL();
	$forward_dest = array();
	foreach($return as $line)
	{
		$tmp = explode(" ",$line);
		if(count($tmp) == 4)
		{
			$forward_dest[$tmp[3]."|".$tmp[2]] = intval($tmp[1]);
		}
		else
		{
			$forward_dest[$tmp[2]] = intval($tmp[1]);
		}
	}

	$result = array('forward_destinations' => $forward_dest);
	$data = array_merge($data, $result);
}

if (isset($_GET['getQueryTypes']) && $auth)
{
	sendRequestFTL("querytypes");
	$return = getResponseFTL();
	$querytypes = array();
	foreach($return as $ret)
	{
		$tmp = explode(": ",$ret);
		$querytypes[$tmp[0]] = intval($tmp[1]);
	}

	$result = array('querytypes' => $querytypes);
	$data = array_merge($data, $result);
}

if (isset($_GET['getAllQueries']) && $auth)
{
	if(isset($_GET['from']) && isset($_GET['until']))
	{
		// Get limited time interval
		sendRequestFTL("getallqueries-time ".$_GET['from']." ".$_GET['until']);
	}
	else if(isset($_GET['domain']))
	{
		// Get specific domain only
		sendRequestFTL("getallqueries-domain ".$_GET['domain']);
	}
	else if(isset($_GET['client']))
	{
		// Get specific client only
		sendRequestFTL("getallqueries-client ".$_GET['client']);
	}
	else
	{
		// Get all queries
		sendRequestFTL("getallqueries");
	}
	$return = getResponseFTL();
	$allQueries = array();
	foreach($return as $line)
	{
		$tmp = explode(" ",$line);
		array_push($allQueries,$tmp);
	}

	$result = array('data' => $allQueries);
	$data = array_merge($data, $result);
}

if(isset($_GET["recentBlocked"]))
{
	sendRequestFTL("recentBlocked");
	die(getResponseFTL()[0]);
	unset($data);
}

if (isset($_GET['overTimeDataForwards']) && $auth)
{
	sendRequestFTL("ForwardedoverTime");
	$return = getResponseFTL();

	foreach($return as $line)
	{
		$tmp = explode(" ",$line);
		for ($i=0; $i < count($tmp)-1; $i++) {
			$over_time[intval($tmp[0])][$i] = intval($tmp[$i+1]);
		}
	}
	$result = array('over_time' => $over_time);
	$data = array_merge($data, $result);
}

if (isset($_GET['getForwardDestinationNames']) && $auth)
{
	sendRequestFTL("forward-names");
	$return = getResponseFTL();
	$forward_dest = array();
	foreach($return as $line)
	{
		$tmp = explode(" ",$line);
		if(count($tmp) == 4)
		{
			$forward_dest[$tmp[3]."|".$tmp[2]] = intval($tmp[1]);
		}
		else
		{
			$forward_dest[$tmp[2]] = intval($tmp[1]);
		}
	}

	$result = array('forward_destinations' => $forward_dest);
	$data = array_merge($data, $result);
}

if (isset($_GET['overTimeDataQueryTypes']) && $auth)
{
	sendRequestFTL("QueryTypesoverTime");
	$return = getResponseFTL();

	foreach($return as $line)
	{
		$tmp = explode(" ",$line);
		for ($i=0; $i < count($tmp)-1; $i++) {
			$over_time[intval($tmp[0])][$i] = intval($tmp[$i+1]);
		}
	}
	$result = array('over_time' => $over_time);
	$data = array_merge($data, $result);
}

disconnectFTL();
?>
