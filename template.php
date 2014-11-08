<?php 

if (isset($_GET['orderby'])){

	$url=$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	$url=explode('?',$url);
	$url=$url[0];
	header ('HTTP/1.1 301 Moved Permanently');  
	header ('Location: http://'.$url);
}

$checkforindex=$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
if(strstr($checkforindex,'/index.php')){
	$checkforindex=str_replace('index.php','',$checkforindex);
	header ('HTTP/1.1 301 Moved Permanently');  
	header ('Location: http://'.$checkforindex);
}

include 'functions.inc';
dbconnect();

// check for banned ip adresses 
$remoteip=getenv('REMOTE_ADDR');	
ipban($remoteip);

// check for active user session
session_start();
$currentusrname=$_SESSION['currentusrname'];
$currentpkusr=$_SESSION['currentpkusr'];

//check for log out action
$logout=$_POST['logout'];
if($logout){
	session_destroy();
	$currentusrname="";
	$currentpkusr="";
}



if (isset($_SERVER['HTTP_REFERER'])) { 

	$uri = parse_url($_SERVER['HTTP_REFERER']); 
	$refsource= $uri['host']; 
	mysql_query("UPDATE tbllistings set referrating=(referrating+1) WHERE weburl LIKE '%$refsource%'");
	include 'updateranking.inc'; 
}


// BEGIN URL ANALYSIS
$domain=$_SERVER['HTTP_HOST'] ;/*get domain name*/
$cwd = $_SERVER[PHP_SELF];
$dir = dirname($cwd);  
$parts = Explode('/', $cwd);/*break path at forward slash*/
$file = $parts[count($parts) - 1];
$region = $parts[count($parts) - 2];
$bits = Explode('.', $file);/*break file name at dot*/
$page = $bits[count($bits) - 2];/* page*/

// BEGIN PAGE VARIABLE BUILD
if ($page=='index')$page='Home'; /* replace value of $page with Home for all index files*/
$region=str_replace("_", " ", $region);
$page = ucfirst($page);
$categoriesquery="SELECT * FROM tblcategories" ;
$categorylist=mysql_query($categoriesquery);
$catnum=mysql_numrows($categorylist);

for ($cat=0; $cat<$catnum; $cat++){
	if (str_replace("_", " ", $page)==mysql_result($categorylist, $cat, categoryname)){
	$pagetype='category'; 
	$category=$page;
	}
}			 

$categoriesquery="SELECT * FROM tblcategories" ;
$categorylist=mysql_query($categoriesquery);
$catnum=mysql_numrows($categorylist);

for ($cat=0; $cat<$catnum; $cat++){
	if ($region==mysql_result($categorylist, $cat, categoryname)){
	$pagetype='listing'; 
	$listingname=str_replace("_", " ", $page);
	}
}			 

$islandsquery="SELECT * FROM tblislands ";
$islandlist=mysql_query($islandsquery);
$islnum=mysql_numrows($islandlist);

for ($isl=0; $isl<$islnum; $isl++){
	if ($region==mysql_result($islandlist, $isl, islandname)){
	$regiontype='island';
	if (!$pagetype) $pagetype=$regiontype;
	}
}  

$citiesquery="SELECT * FROM tblcities" ;
$citylist=mysql_query($citiesquery);
$citnum=mysql_numrows($citylist);

for ($cit=0; $cit<$citnum; $cit++){
	if ($region==mysql_result($citylist, $cit, cityname)){
	$regiontype='city';
	if (!$pagetype) $pagetype=$regiontype; 
	}
}

if (!$pagetype) $pagetype='state';
if (!$pagetype or ($pagetype=='state' and $file!='index.php')) $pagetype='unique';

switch ($pagetype){

	case 'island' :
	$file = $parts[count($parts) - 1]; 
	$island = $parts[count($parts) - 2];
	case 'city' :
	$file = $parts[count($parts) - 1]; 
	$city = $parts[count($parts) - 2];
	$island = $parts[count($parts) - 3];
	default :
		switch (count($parts)){
			case '2' : 
			$file = $parts[count($parts) - 1]; 
			$region = 'Hawaii';
			break;
			case '3' : 
			$file = $parts[count($parts) - 1]; 
			$island = $parts[count($parts) - 2];
			$region = $parts[count($parts) - 2];
			break;
			case '4' : 
			$file = $parts[count($parts) - 1]; 
			$city = $parts[count($parts) - 2];
			$region = $parts[count($parts) - 2];
			$island = $parts[count($parts) - 3];
			break;
			case '5' : 
			$file = $parts[count($parts) - 1]; 
			$category = $parts[count($parts) - 2];
			$city = $parts[count($parts) - 3];
			$region = $parts[count($parts) - 3];
			$island = $parts[count($parts) - 4];
			break;						
			default :
			$region='Hawaii';
			$file = $parts[count($parts) - 1];
			break;
		}
}

$state='Hawaii';

if (!$regiontype) $regiontype='state';
$region=str_replace("_", " ", $region);
$island=str_replace("_", " ", $island);
$category=str_replace("_", " ", $category);
$page=str_replace("_", " ", $page);

if (($page=='Home')or($pagetype=='unique')) $meta_page='Adventure Travel';
else $meta_page=$page;

if ($regiontype=='island') $region_preposition='on';
else $region_preposition='in';

if ($pagetype=='listing')$pagetitle=$category; 

if (!$pagetitle) $pagetitle=$page;

if ($pagetype=='unique'){$pagetitle='Adventure Travel'; $regiontype='state';}

if ($pagetitle=='Home')$pagetitle='Adventure Travel';

$pagetitle=pluraltosingular($pagetitle);

// UNCOMMENT THIS SECTION TO DISPLAY VALUES FOR TROUBLESHOOTING
/*
echo 'domain = '.$domain.'<br>';
echo 'cwd = '.$cwd.'<br>';
echo 'dir = '.$dir.'<br> ';
echo 'parts count = '.count($parts).'<br>';
for ($p=0; $p < count($parts); $p++){
echo 'part['.$p.'] = '.$parts[$p].'<br>';
}
echo 'file = '.$file.'<br>';
echo 'bits count = '.count($bits).'<br>';
for ($b=0; $b < count($bits); $b++){
echo 'bit['.$b.'] = '.$bits[$b].'<br>';
}
echo 'page = '.$page.'<br>';  
echo 'region='.$region.'<br>';   
echo 'pagetype = '.$pagetype ;
*/

//BEGIN LISTING VISIT COUNTER
if ($pagetype=='listing'){
	$currentlistingurl=$domain.$cwd;
	$currentrequestratingquery="SELECT * FROM tbllistings WHERE athurl = '$currentlistingurl' ";
	$currentrequestratingresult=mysql_query($currentrequestratingquery);
	$currentrequestrating=mysql_result($currentrequestratingresult,0,"requestrating");
	$lastip=mysql_result($currentrequestratingresult,0,"lastip");
	$pk=mysql_result($currentrequestratingresult,0,"pklistings");
	$taglistingname=mysql_result($currentrequestratingresult,0,"listingname");
	
	if($remoteip!=$lastip){
	$adjustedrequestrating=($currentrequestrating + 1);
	$adjustedrequestratingquery="UPDATE tbllistings SET requestrating='$adjustedrequestrating' WHERE athurl = '$currentlistingurl'";
	mysql_query($adjustedrequestratingquery);
	}
	
	// SET LAST IP TO PREVENT VISIT COUNT SPAMMING 
	$newipquery="UPDATE tbllistings SET lastip='$remoteip' WHERE athurl = '$currentlistingurl'";
	mysql_query($newipquery);
	
	//LOG USER LISTING BROWSE
	$iploggingquerey="INSERT INTO tbliplogging SET fklistings='$pk', fkusr='$currentpkusr', remoteip='$remoteip'";
	mysql_query($iploggingquerey);						
}			

//UPDATE RANKINGS
$update=$_GET['u'];
//$update=TRUE; // toggle update on every page load by changing value (TRUE=on FALSE=off)
if ($update){
include 'updateranking.inc';
}

//Check for tags if none are found add tags for island region and category
/*
if($_GET['t']){

	if ($page != 'Tag_index')checkForTags($island, $region, $category, $pagetype, $page, $taglistingname);
	
	if ($pagetype == 'category' and $regiontype=='city'){
	
		$inserttagurinoindex=$_SERVER['REQUEST_URI'];
		$tagblockquery="INSERT INTO tbltagurinoindex VALUES ('','$inserttagurinoindex')";
		mysql_query($tagblockquery);
		set_site_updated();
	}
}
//END TAG CHECK
if ($_POST['postusertag']){
	include 'process_tags.inc';
	set_site_updated();
}
// BEGIN DOCUMENT
*/
echo
'<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">'."\n",
'<html>'."\n",
'<head>'."\n",
'<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">'."\n",
'<meta http-equiv="Content-Language" content="en-us">'."\n",
'<meta name="identifier" content="http://'.$domain.'">'."\n",
'<meta name="coverage" content="Global">'."\n",
'<meta name="country" content="United States">'."\n",
'<meta name="rating" content="General">'."\n",
'<meta name="author" content="Adventure Travel Hawaii">'."\n",
'<meta name="copyright" content="2013 Adventure Travel Hawaii">'."\n",
'<meta name="robots" content="index, follow">'."\n";

?>
<meta property="og:title" content="Adventure Travel Hawaii" />
<meta property="og:type" content="website" />
<meta property="og:url" content="http://www.adventure-travel-hawaii.com" />
<meta property="og:image" content="http://www.adventure-travel-hawaii.com/img/adventure-travel-hawaii-logo.png" />
<meta property="og:site_name" content="Adventure Travel Hawaii" />
<meta property="fb:admins" content="698309523" />
<?


$metadatapath=$_SERVER['REQUEST_URI'];
$metadataquery="SELECT * FROM tblmetadata WHERE url='$metadatapath' limit 1" ;
$metadatainfo=@mysql_query($metadataquery); 
$db_abstr=@mysql_result($metadatainfo, 0, abstr);
$db_description=@mysql_result($metadatainfo, 0, description);
$db_keywords=@mysql_result($metadatainfo, 0, keywords);
$db_title=@mysql_result($metadatainfo, 0, title);	 

if ($db_abstr) echo '<meta name="abstract" content="'.$db_abstr.'">'."\n";
else echo '<meta name="abstract" content="Adventure Travel Hawaii - photos reviews and links to adventure travel resources on Oahu Maui Big Island Kauai Molokai and Lanai.">'."\n";    

if ($page=='Tag index')echo '<meta name="description" content="'.$_REQUEST['tag'].' Tag index. Adventure Travel Hawaii pages ranked in order of user votes for the tag-'.$_REQUEST['tag'].'>'."\n";
elseif ($db_description) echo '<meta name="description" content="'.$db_description.'">'."\n";
else echo '<meta name="description" content="'.$region.' '.$meta_page.' - Reviews, news articles, in depth descriptions, photographs and links to '.$meta_page.' '.$region_preposition.' '.$region.'.">'."\n";

if ($db_keywords) echo '<meta name="keywords" content="'.$db_keywords.'">'."\n";
else {
echo '<meta name="keywords" content="'; 
update_keywords(); 
echo '">'."\n";
}
if ($db_title) $title=$db_title;

else{
	if ($page=='Home')$title=( $region . ' Adventure Travel  -  Guide to adventure travel '.$region_preposition.' '.$region.'.');
	elseif ($pagetype=='listing') $title=( $page .' -  Reviews, and direct links to '.$meta_page.' '.$region_preposition.' ' . $region . '.');
	else $title=( $region . ' ' . $page . '  - Guide to '.$meta_page.' '.$region_preposition.' ' . $region . '.'); 
}
if ($page=='Tag index'){
$title= $_REQUEST['tag'].' pages - Ranked index of pages tagged "'.$_REQUEST['tag'].'"';
}

echo 
'<title>'.$title.'</title>'."\n",
'<link rel="stylesheet" type="text/css" href="http://'.$_SERVER['HTTP_HOST'].'/style.css">'."\n",
'<link rel="shortcut icon" type="image/x-icon" href="http://'.$_SERVER['HTTP_HOST'].'/favicon.ico">'."\n",
'<link rel="alternate" type="application/rss+xml" title="Adventure Travel Hawaii - New Listings" href="http://www.adventure-travel-hawaii.com/rss/" >',
'<script type="text/javascript" src="http://'.$_SERVER['HTTP_HOST'].'/topnav.js"></script>'."\n";


if ($page=='Submit')include 'submitscripts.inc';
if ($page=='Sitemap')include 'sitemapheader.inc';


echo
'<meta name="msvalidate.01" content="82B99AEA0505A75FC577EEC7298FB1A5" >',
'<meta name="google-site-verification" content="QIeVfcste96qK3Nj8lUydkIJWgR1HY-kFiFqgdyMZH8" >',
'<META name="y_key" content="6d5c0dd770ae891c">',
'</head>'."\n",
'<body>'."\n",
'<div id="hawaii_travel">'."\n",
//BEGIN BRANDING
'<div id="adventure_travel_hawaii">'."\n",
'<a href="/">',
'<img src="/img/adventure-travel-hawaii-logo.png" alt="Adventure Travel Hawaii Home" align="left" style="margin:6px 0 0 6px;border:none;padding:0;">',
'</a>';

echo '<!-- '.$refsource.' -->';

if (($pagetype == 'state') or( $pagetype == 'unique')){
	echo'<h1>Adventure Travel Hawaii</h1>'."\n";
}else{

	if ($pagetype=='category')$headerappend=' Guide';
	echo'<h1> '.$region.' '.$pagetitle.$headerappend.'</h1>'."\n";
}

if($pagetype=='listing')echo '<b>'. $meta_page.' '.$region_preposition.' '.$region.' Hawaii. Detailed description, images, reviews, and ratings.</b>'."\n";
else echo '<b>Guide to '. $meta_page.' '.$region_preposition.' '.$region.'. Reviews posted by adventure travelers. Descriptions, photos, and direct links.</b> '."\n";

echo
'</div>';
//END BRANDING 
// BEGIN TOPNAV 
echo
'<div id="navigatgion">'."\n",
'<ul id="topnav" >'."\n";	

$query="SELECT * FROM tblislands WHERE linksdisplayed>0 ORDER BY displaypriority";
$tblislands=mysql_query($query);
$inum=mysql_numrows($tblislands);
$isl=0;

while ($isl < $inum) {
	$pkislands=mysql_result($tblislands,$isl,"pkislands");
	$islandname=mysql_result($tblislands,$isl,"islandname");
	
	if ($isl==($inum-1)){$rightborder='2';}
	
	echo ('<li class="islandbutton'.$rightborder.'" ><a href="/' . str_replace(" ", "_", $islandname) . '/">' . $islandname . '</a>')."\n";
	
	$query="SELECT * FROM tblcities WHERE fkislands=$pkislands AND linksdisplayed>0";
	$tblcities=mysql_query($query);
	
	if ($tblcities){
			$cnum=mysql_numrows($tblcities);
			echo '<ul>'."\n";
			$c=0;
			
			while ($c < $cnum) {
				$pkcities=mysql_result($tblcities,$c,"pkcities");
				$cityname=mysql_result($tblcities,$c,"cityname");
				echo ('<li><a href="/' . str_replace(" ", "_", $islandname) . '/' . str_replace(" ", "_", $cityname) . '/" >' . $cityname . '</a></li>')."\n";
				$c++;
			}
			echo '</ul></li>'."\n";
	}
	$isl++;
}
echo 
'</ul></div>'."\n"; 
// END TOPNAV


//BEGIN MAIN
echo
'<div id="main">';
//BEGIN CENTER

echo
'<div class="column" id="center">'."\n";

//BEGIN CONTENT 

// this section is used to insert custom content into a regional category page such as special events or advertisements
// simply create a content page and save it in the includes/category as region_category.inc ie honolulu_nightlife.inc

if ($pagetype=='category') {

	echo '<div class="content" id="catinfo">';
	
	if ($category=='Hostels' or $category=='Hotels' or $category=='Bed and Breakfast'){
	?><a href='/Book_Hostels.php'><img src='http://reservations.bookhostels.com/images/abh/promos/banners_2009/Banner038-728x90-english-usd-all_properties-non_groups.jpg' border=0 alt='Book Hostels Online Now'></a><?
	}
	
	if ($category=='Nightclubs and Bars'){
	?><a href='http://www.hawaiinightlifeguide.com/'><img src='/img/hawaiinightlifeguide.jpg' border=0 alt='Hawaii Nightlife Guide -  Reviews, Descriptions, photos, and links to the best Nightlife in Hawaii.'></a><?
	}
	
	
	if ($category=='other')$cat="";
	else $cat=$category;
	$cat=pluraltosingular($cat);
	
	$pagecontentheadline=$region.' '.$category;
	
	$regioncontent='category/'.str_replace(" ", "_", $region).'_'.str_replace(" ", "_", $category).'.inc';
	if (file_exists('/home2/adventu1/includes/'.$regioncontent)){
		echo '<div id="category_content">';
		include $regioncontent;
		echo '</div>';
	}
	

}else{		
// end custom category_region content

	echo'<div class="content" id="info" '.$infoborder.'>'."\n";
  	if ($category=='other')$cat="";
	else $cat=$category;
	$cat=pluraltosingular($cat);


	if (($pagetype=='state')or($pagetype=='island')or($pagetype=='city')){// REGIONAL PAGES
		
		switch($pagetype){
		
			case 'state':
			$pagecontentheadline=$region.' Islands Guide';
			break;
			
			case 'island':
			$pagecontentheadline=$region.' Island Guide';
			break;
			
			case 'city':
			$pagecontentheadline=$region.' City Guide';
			break;
			
			default:
			$pagecontentheadline=$region.' Guide';
			break;
		}
		
		
		echo 
		'<h2 class="centerh2">'.$pagecontentheadline.'</h2><br style="line-height:10px;">';
		include ('../includes/regional/'.str_replace(" ", "_", $region).'.htm');
	}
	
	if ($pagetype=='listing'){
		$currentlistingurl=$domain.$cwd;
		$query="SELECT * FROM tbllistings WHERE athurl = '$currentlistingurl' ";
		$result=mysql_query($query);
		
		$pklistings =mysql_result($result,0,"pklistings");
		$isapproved =mysql_result($result,0,"isapproved");
		$isfeatured =mysql_result($result,0,"isfeatured");
		$isrecommended =mysql_result($result,0,"isrecommended");
		$athrating =mysql_result($result,0,"athrating");
		$athurl =mysql_result($result,0,"athurl");
		$bookingenabled=@mysql_result($result,0,bookingenabled);
		$bookingurl=@mysql_result($result,0,bookingurl);
		$customized=@mysql_result($result,0,customized);
		$reviewrating =mysql_result($result,0,"reviewrating");
		$overallrating =mysql_result($result,0,"overallrating");
		$subname =mysql_result($result,0,"subname");
		$subemail =mysql_result($result,0,"subemail");
		$subtime =mysql_result($result,0,"subtime");
		$fkislands =mysql_result($result,0,"fkislands");
		$fkcategories =mysql_result($result,0,"fkcategories");
		$listingname =mysql_result($result,0,"listingname");
		$street =mysql_result($result,0,"street");
		$fkcities =mysql_result($result,0,"fkcities");
		$fkstates =mysql_result($result,0,"fkstates");
		$zip =mysql_result($result,0,"zip");
		$weburl =mysql_result($result,0,"weburl");
		$facebookurl=mysql_result($result,0,"facebookurl");
		$twitterurl=mysql_result($result,0,"twitterurl");
		$freindfeedurl=mysql_result($result,0,"freindfeedurl");
		$email =mysql_result($result,0,"email");
		$phone1 =mysql_result($result,0,"phone1");
		$phone2 =mysql_result($result,0,"phone2");
		$fax =mysql_result($result,0,"fax");
		$title =mysql_result($result,0,"title");
		$descshort =mysql_result($result,0,"descshort");
		$paragraph1 =mysql_result($result,0,"paragraph1");
		$paragraph2 =mysql_result($result,0,"paragraph2");
		$paragraph3 =mysql_result($result,0,"paragraph3");
		$img = str_replace(" ", "", $listingname);
		$img = str_replace("'", "", $img);
		$custominclude=str_replace(" ", "_", $listingname).'.inc';
		
		$useraction=$_POST['useraction'];
		if($currentusrname==$subname AND $useraction=='editlisting'){
			include 'editlisting_user.inc';
		}
		
		else{		
			if($customized)include ('customized/'.strtolower($custominclude));
			
			else{
				if (!$isfeatured)$nofollow='rel="nofollow"';
				else $nofollow='';
			
				if($weburl)echo '<h2 class="centerh2"><a href="http://'.$weburl.'" '.$nofollow.' target="new" onClick="javascript:urchinTracker(\'/external/'.str_replace(".", "_", $weburl).'\');" >'.$listingname.'</a></h2>'."\n";
				else echo '<h2 class="centerh2">'.$listingname.'</h2>'."\n";
				$pagecontentheadline=$listingname;
				echo
				'<table id="listingimage" summary="" cellspacing=0 cellpadding=0 align="left"style=" margin-top:10px;padding:0;">',
				//'<tr><td align="center"><b><a href="'.$PHP_SELF.'#reviews">Jump to Reviews</a></b></td></tr>'."\n",
				'<tr><td>'."\n";
				
				if($img) echo '<div id="imgshadow"><img src="http://'.$domain.'/img/'.$img.'.jpg" alt="'.$region.' '.$category.' - '.$listingname.'"></div>'."\n";
				echo
				'</td></tr><tr><td align="center"><b>'.$title.'</b></td></tr></table>'."\n",
				'<p  style="margin-top:10px; padding-top:0;" ><b>'.$descshort.'</b><br><br>'.$paragraph1.'</p>'."\n";
				
				if ($paragraph2) echo '<p >'.$paragraph2.'</p>'."\n";
				if ($paragraph3) echo '<p >'.$paragraph3.'</p>'."\n";
				if ( $facebookurl or $twitterurl or $freindfeedurl){
					echo '<br><div class="follow">';
					if ($facebookurl)echo' <a href="http://'.$facebookurl.'" rel="nofollow" title="Follow '.$listingname.' on Facebook"> <img src="/img/follow_on_facebook.png" alt="follow '.$listingname.' on facebook" width="48" height="48" border="0" > </a>';
					if ($twitterurl)echo' <a href="http://'.$twitterurl.'" rel="nofollow" title="Follow '.$listingname.' on Twitter"> <img src="/img/follow_on_twitter.png" alt="follow '.$listingname.' on twitter" width="48" height="48"  border="0"> </a>';
					if ($freindfeedurl)echo' <a href="http://'.$freindfeedurl.'" rel="nofollow" title="Follow '.$listingname.' on Friendfeed"> <img src="/img/follow_on_friend_feed.png" alt="follow '.$listingname.' on friend feed" width="48" height="48"  border="0"> </a>';
					echo '</div>';
				}
				echo '<table summary="" width="80%" align="center" style="clear: both;"><tr><td align="center"><br>'."\n",
				'<address><b>'.$listingname.'</b><br>'."\n",
				$street.'<br>'."\n",
				$city.' HI, '.$zip."\n",
				'</address>'."\n",
				'</td><td align="center"><br>'."\n";
				
				if ($phone1)echo 'Phone: '.$phone1.'<br>'."\n";
				if ($phone2)echo 'Phone: '.$phone2.'<br>'."\n";
				if ($fax)echo 'Fax: '.$fax."\n";
				
				echo '</td></tr></table>'."\n";
				if ($email)echo '<p style="text-align:center;">'.$email.'</p>'."\n";	
				
				if ($bookingenabled){
					echo'<p style="width:95%;padding:3px;font-size:90%;text-align:center;background:#ddddee;">'.$listingname.' is taking adantage of our <a href="/webmasters.php#custom"><b>Custom Listing</b></a> option to enable <a href="http://'.$bookingurl.'" target="new" onClick="javascript:urchinTracker(\'/external/'.str_replace(".", "_", $bookingurl).'\');">Direct Bookings</a>.</p>'."\n";
				}
			}
		}
	}
	
	else {
		if ($pagetype=='unique'){
			$page=str_replace(" ", "_", $page);
			include ('content/'.strtolower($page).'.inc');
		}
	}
}	

echo '</div>'."\n";

//END CONTENT
?>
        <div style="width:660px;padding:10px;margin:auto;">
        <script src="http://connect.facebook.net/en_US/all.js#xfbml=1"></script><fb:like href="http://www.facebook.com/pages/Adventure-Travel-Hawaii/328757255385" show_faces="true" width="680"></fb:like>
        </div>
        <?

//BEGIN LISTINGS 


	if ($pagetype=='listing'){
		$reviewquery="SELECT * FROM tbluserreviews WHERE fklistingreviewed='$pklistings' AND reviewapproved='1' ORDER BY reviewsubtime DESC";
		$reviewresult=mysql_query($reviewquery);
		$reviewnum=mysql_numrows($reviewresult);
		echo '<div id="reviews" >'."\n";
		
		
		if ($reviewnum<1){
			echo'<div style="border:0;margin:5px 0 25px 0;">',
				reviewbutton($pklistings);
				echo '<b style="display:inline;margin:0 0 0 10px;font-size:120%;">Be the first to review '.$listingname.'.</b>',
				'</div>';
		}
	
		$listlength=$reviewnum;
		
		if ($reviewnum > 0 ){
			echo
				'<div style="margin:10px 0 10px 0;">',
				'<span style="line-height:30px;font-weight:bold;font-size:20px;">Reviews Posted By Adventure Travel Hawaii Members</span>';
				
			if (($pagetype=='listing') AND ($currentusrname==$subname)){
			  echo
				'<form  style="margin:0;display:inline;" action="'.$_SERVER['PHP_SELF'].'" enctype="multipart/form-data" method="post" >',
				'<input type="hidden" name="useraction" value="editlisting">',
				'<input type="hidden" name="fklistings" value="'.$pklistings.'">',			
				'<input style="margin:2px 0 0 15px;" type="submit" value="Update Listing">',
				'</form>'; 
			}else{
				echo
				'<form style="margin:0;display:inline;" action="/review.php" enctype="multipart/form-data" method="post" >'."\n",
				'<input type="hidden" name="fklistingreviewed" value="'.$pklistings.'">'."\n",
				'<input type="hidden" name="fkcategories" value="'.$fkcategories.'">'."\n",
				'<input type="hidden" name="listingname" value="'.$listingname.'">'."\n",
				'<input type="submit" style="margin:2px 0 0 15px;" value="Post A Review">'."\n",
				'</form>'."\n";
			}
			
			echo'</div>';
				
			for ($r=0; $r<$reviewnum; $r++){
				$reviewsubtime=strtotime(mysql_result($reviewresult,$r,reviewsubtime));
				$reviewname=@mysql_result($reviewresult,$r,reviewname);
				$reviewemail=@mysql_result($reviewresult,$r,reviewemail);
				$rev_crit1=@mysql_result($reviewresult,$r,rev_crit1);
				$rev_crit2=@mysql_result($reviewresult,$r,rev_crit2);
				$rev_crit3=@mysql_result($reviewresult,$r,rev_crit3);
				$rev_crit4=@mysql_result($reviewresult,$r,rev_crit4);
				$rev_crit5=@mysql_result($reviewresult,$r,rev_crit5);
				$reviewrating=mysql_result($reviewresult,$r,reviewrating);
				$reviewcomments=mysql_result($reviewresult,$r,reviewcomments);
				$reviewcomments=stripslashes($reviewcomments);
				$hideemail=mysql_result($reviewresult,$r,hideemail);
				
				$listingdataquery="SELECT * FROM tbllistings WHERE pklistings=$pklistings LIMIT 1" ;
				$result=mysql_query($listingdataquery);
				$fkcategories=mysql_result($result,0,fkcategories);
				
				$cat_critquery="SELECT * FROM tblcategories WHERE pkcategories=$fkcategories LIMIT 1";
				$result=mysql_query($cat_critquery);
				$cat_crit1=mysql_result($result,0,cat_crit1);
				$cat_crit2=mysql_result($result,0,cat_crit2);
				$cat_crit3=mysql_result($result,0,cat_crit3);
				$cat_crit4=mysql_result($result,0,cat_crit4);
				$cat_crit5=mysql_result($result,0,cat_crit5);
				
				if ($hideemail==1)$reviewemail='Private';
				else $reviewemail=('<a href="mailto:'.$reviewemail.'">'.$reviewemail.'</a>');
				
				echo
				'<div class="reviewdisplay">'."\n",
				'<div class="overallrating">Overall '.$reviewrating.'%</font>',
				'<b>Posted by: '.ucfirst($reviewname).' on '.(date("F j, Y g:i a", $reviewsubtime)).'&nbsp;&nbsp; E-mail: '.$reviewemail.'</b>',
				'</div>'."\n",
				'<div class="scorebox">'."\n",
				'<table  cellspacing=0 cellpadding=0>'."\n",
				'<tr><td>'.$cat_crit1.'</td> <td style="padding-left:10px;">'.($rev_crit1*20).'%</td></tr>'."\n",
				'<tr><td>'.$cat_crit2.'</td> <td style="padding-left:10px;">'.($rev_crit2*20).'%</td></tr>'."\n",
				'<tr><td>'.$cat_crit3.'</td> <td style="padding-left:10px;">'.($rev_crit3*20).'%</td></tr>'."\n",
				'<tr><td>'.$cat_crit4.'</td> <td style="padding-left:10px;">'.($rev_crit4*20).'%</td></tr>'."\n",
				'<tr><td>'.$cat_crit5.'</td> <td style="padding-left:10px;">'.($rev_crit5*20).'%</td></tr>'."\n",
				'</table>'."\n",
				'</div>'."\n",
				'<div >'."\n",
				'<span class="reviewcomments">'."\n",
				'<p style="margin:5px 0 0 0;padding:0;">Comments: '.$reviewcomments.'</p></span>'."\n",
				'</div>'."\n",
				'<p style="clear:both;margin:0;padding:0;line-height:1px;">&nbsp;</p>'."\n",
				'</div>'."\n"; 
			}
			if ($reviewnum>0)reviewbutton($pklistings);	
		}
		
		echo '</div>'."\n";
		
		
		
		leaderboardLinks();
	}
	
	else{
		
		echo '<a id="list" name="list" style="display:hidden;">&nbsp;</a>';
	
		leaderboardLinks();
		
	if ($pagetype!='unique'){
	
		echo '<div id="listings" >'."\n";

			$orderby=$_POST['orderby'];
		
			if(!$orderby){
			
				if ($pagetype=='state')$orderby='pklistings DESC';
				else $orderby='overallrating DESC, requestrating DESC';
			}
			
			switch ($orderby){
				
				case 'overallrating DESC, requestrating DESC' :
					$sortText='Top Overall Ranking ';
				break;
				case 'reviewrating DESC, requestrating DESC' :
					$sortText='Top User Rated ';
				break;
				case 'pklistings DESC' :
					$sortText='Most Recent ';
				break;
				case 'popularityindex DESC, requestrating DESC' :
					$sortText='Most Popular ';
				break;
			}
		
			if ($pagetype=='category'){
			echo '<h2 class="centerh2">',
			$sortText.$region.' '.$category.'</h2>'."\n";
			}
			
			else{
				echo '<h2 class="centerh2">',
				$sortText.$region.' Listings</h2>'."\n";
			}
			
			echo
				'<div id="sortlistings">Sort Listings By: '."\n",
				'<form action="'.str_replace('index.php','',$_SERVER['PHP_SELF']).'#list" enctype="multipart/form-data" method="post">'."\n",
				' <input type="hidden" name="orderby" value="overallrating DESC, requestrating DESC">'."\n",
				' <input type="submit" value="Overall Rating" class="sortbybutton">'."\n",
				'</form>'."\n",
				'<form action="'.str_replace('index.php','',$_SERVER['PHP_SELF']).'#list" enctype="multipart/form-data" method="post">'."\n",
				' <input type="hidden" name="orderby" value="reviewrating DESC, requestrating DESC">'."\n",
				' <input type="submit" value="Review Score" class="sortbybutton">'."\n",
				'</form>'."\n",
				'<form action="'.str_replace('index.php','',$_SERVER['PHP_SELF']).'#list" enctype="multipart/form-data" method="post">'."\n",
				' <input type="hidden" name="orderby" value="pklistings DESC">'."\n",
				' <input type="submit" value="Newest First" class="sortbybutton">'."\n",
				'</form>'."\n",
				'<form action="'.str_replace('index.php','',$_SERVER['PHP_SELF']).'#list" enctype="multipart/form-data" method="post">'."\n",
				' <input type="hidden" name="orderby" value="popularityindex DESC, requestrating DESC">'."\n",
				' <input type="submit" value="Popularity" class="sortbybutton" style="width:70px;">'."\n",
				'</form>'."\n",
				'</div>'."\n";
		
		
		$categorycountquery="SELECT * FROM tblcategories WHERE displaypriority<'9999'";
		$categorycountresult=mysql_query($categorycountquery);
		$categorycount=mysql_numrows($categorycountresult);
		
		
		if ($pagetype != 'category' and $pagetype !='unique'){
		
			switch ($pagetype){
			
				case 'unique' :
				//insert code for unique top level pages
				break;
				
				case 'state' :
				$statequery="SELECT * FROM tblstates WHERE statename='$region' ";
				$stateresult=mysql_query($statequery);
				$pkstates=mysql_result($stateresult,0,pkstates);
				$listingquery="SELECT * FROM tbllistings WHERE fkstates='$pkstates' AND isapproved='1' ORDER BY $orderby limit 0,10 ";
				$listingresult=mysql_query($listingquery);
				$rnum=mysql_numrows($listingresult);
				break;
				
				case 'island' :
				$islandquery="SELECT * FROM tblislands WHERE islandname='$region'";
				$islandresult=mysql_query($islandquery);
				$pkislands=mysql_result($islandresult,0,pkislands);
				$listingquery="SELECT * FROM tbllistings WHERE fkislands='$pkislands' AND isapproved='1' ORDER BY $orderby limit 0,10";
				$listingresult=mysql_query($listingquery);
				$rnum=mysql_numrows($listingresult);
				break;
				
				case 'city' :
				$cityquery="SELECT * FROM tblcities WHERE cityname='$region' ";
				$cityresult=mysql_query($cityquery);
				$pkcities=mysql_result($cityresult,0,pkcities);
				$listingquery="SELECT * FROM tbllistings WHERE fkcities='$pkcities' AND isapproved='1' ORDER BY $orderby limit 0,10";
				$listingresult=mysql_query($listingquery);
				$rnum=mysql_numrows($listingresult);
				break;
			}
			
			$listlength=$rnum;
			
			if ($rnum<1 and $region !='Hawaii'){
				echo '<p><a href="/submit.php">Submit</a> the first listing for '.$region.'.</p>'."\n";
			}
			
			else {
			
				for ($r=0; $r<$rnum; $r++){
				
					$listingname=mysql_result($listingresult,$r,listingname);
					$descshort=mysql_result($listingresult,$r,descshort);
					$athurl=mysql_result($listingresult,$r,athurl);
					$reviewrating=mysql_result($listingresult,$r,reviewrating);
					$reviewcount=mysql_result($listingresult,$r,reviewcount);
					$popularityindex=mysql_result($listingresult,$r,popularityindex);
					$requestrating=mysql_result($listingresult,$r,requestrating);
					$img = str_replace(" ", "", $listingname);
					$img = str_replace("'", "", $img);
					if ($reviewcount==0)$reviewalt=$listingname.' has no user reviews posted ';
					else{
						if ($reviewcount==1)$thisrevtext='review';
						else $thisrevtext='reviews';
						$reviewalt=$reviewrating.'% Based on '.$reviewcount.' user '.$thisrevtext.' ';
					}
					
					lbt($domain, $athurl, $listingname, $descshort, $img, $reviewalt, $reviewrating, $popularityindex, $requestrating);
				}
			}
		}
		
		if ($pagetype=='category'){
			
			$categoryquery="SELECT * FROM tblcategories WHERE categoryname='$category' ";
			$categoryresult=mysql_query($categoryquery);
			$pkcategories=mysql_result($categoryresult,0,pkcategories);
			
			switch ($regiontype){
			
				case 'state' :
				$statequery="SELECT * FROM tblstates WHERE statename='$region' ";
				$stateresult=mysql_query($statequery);
				$pkstates=mysql_result($stateresult,0,pkstates);
				$listingquery="SELECT * FROM tbllistings WHERE fkstates='$pkstates' AND ((fkcategories='$pkcategories') OR (pklistings IN (SELECT fklistings FROM links_categories_listings WHERE fkcategories='$pkcategories'))) AND isapproved='1' ORDER BY $orderby";
				$listingresult=mysql_query($listingquery);
				$rnum=mysql_numrows($listingresult);
				break;
				
				case 'island' :
				$islandquery="SELECT * FROM tblislands WHERE islandname='$region' ";
				$islandresult=mysql_query($islandquery);
				$pkislands=mysql_result($islandresult,0,pkislands);
				$listingquery="SELECT * FROM tbllistings WHERE fkislands='$pkislands' AND ((fkcategories='$pkcategories') OR (pklistings IN (SELECT fklistings FROM links_categories_listings WHERE fkcategories='$pkcategories'))) AND isapproved='1' ORDER BY $orderby";
				$listingresult=mysql_query($listingquery);
				$rnum=mysql_numrows($listingresult);
				break;
				
				case 'city' :
				$cityquery="SELECT * FROM tblcities WHERE cityname='$region' ";
				$cityresult=mysql_query($cityquery);
				$pkcities=mysql_result($cityresult,0,pkcities);
				$listingquery="SELECT * FROM tbllistings WHERE fkcities='$pkcities' AND ((fkcategories='$pkcategories') OR (pklistings IN (SELECT fklistings FROM links_categories_listings WHERE fkcategories='$pkcategories'))) AND isapproved='1' ORDER BY $orderby";
				$listingresult=mysql_query($listingquery);
				$rnum=mysql_numrows($listingresult);
				break;
				
				default :
					if ($category=='Other') echo '<p>No '.$category.' listings currently available for '.$region.'.</p>'."\n";
					else echo '<p>View other '.$island.' '.$category.' or<br><a href="/submit.php">Submit</a> the first listing for '.$category.' '.$region_preposition.' '.$region.'.</p>'."\n";
				break;
			}
			
			$listlength=$rnum;
			
			if ($rnum<1){
			
				if ($category=='Other') echo '<p>No '.$category.' listings currently available for '.$region.'.</p><p style="clear:both;">&nbsp;</p>'."\n";
				
				else {echo
						'<p>View other <a href="/'.str_replace(' ','_',$island).'/'.str_replace(' ','_',$category).'.php">'.$island.' '.$category.'</a> or<br>',
						'<a href="/submit.php">Submit</a> the first listing for '.$category.' '.$region_preposition.' '.$region.'.</p>'."\n";
				}
			}else {
			
				for ($r=0; $r<$rnum; $r++){
					$listingname=mysql_result($listingresult,$r,listingname);
					$descshort=mysql_result($listingresult,$r,descshort);
					$athurl=mysql_result($listingresult,$r,athurl);
					$reviewrating=mysql_result($listingresult,$r,reviewrating);
					$reviewcount=mysql_result($listingresult,$r,reviewcount);
					$popularityindex=mysql_result($listingresult,$r,popularityindex);
					$requestrating=mysql_result($listingresult,$r,requestrating);
					$img = str_replace(" ", "", $listingname);
					$img = str_replace("'", "", $img);
					if ($reviewcount==0)$reviewalt=$listingname.' has no user reviews posted ';
					else{
						if ($reviewcount==1)$thisrevtext='review';
						else $thisrevtext='reviews';
						$reviewalt=$reviewrating.'% Based on '.$reviewcount.' user '.$thisrevtext.' ';
					}
					
					lbt($domain, $athurl, $listingname, $descshort, $img, $reviewalt, $reviewrating, $popularityindex, $requestrating);
				}
			}
		}
		
		echo '</div>'."\n";
	}
		echo '<p style="clear:both;">&nbsp;</p>';
	}
	// END LISTINGS
	
	echo
	'</div>'."\n";




//END CENTER 

//BEGIN LEFTNAV 
echo
'<div class="column" id="left">'."\n",
'<div id="leftnav">'."\n";

$query="SELECT * FROM tblcategories WHERE visable=1 ORDER BY displaypriority ";
$tblcategories=mysql_query($query);
$inum=mysql_numrows($tblcategories);

// BEGIN CATEGORY NAV MENU
$dirparts = Explode('/', $dir);/*break path at forward slash*/
if (count($dirparts)>3){
	$regiondir = '/'.$dirparts[count($dirparts) - 3];
	$regiondir = $regiondir.'/'.$dirparts[count($dirparts) - 2];
}
else $regiondir=$dir;

if ($pagetype!='state' and $pagetype!='unique' and $region!='Hawaii')$trailingslash='/';

echo 
'<div id="catnavheader">'."\n",
'<a href="'.$regiondir.$trailingslash.'">'.$region.'</a>'."\n",
'</div>'."\n",
'<div id="catnav">'."\n";

$i=0;
while ($i < $inum) {
	$categoryname=mysql_result($tblcategories,$i,"categoryname");
	
	if ($i == ($inum-1))$nobottom=' style="margin-bottom:0;"';
	
	if ($pagetype=='listing'){
		echo ('<span ><a href="../' . str_replace(" ", "_", $categoryname) .'.php" title="'.$region.' '.$categoryname.'"'.$nobottom.'>'.$categoryname. '</a></span>')."\n";
	}else{
		echo ('<span ><a href="./' . str_replace(" ", "_", $categoryname) .'.php" title="'.$region.' '.$categoryname.'"'.$nobottom.'>'. $categoryname . '</a></span>')."\n";
	}
	
	$i++;
}					

echo
'</div>'."\n";
// END CATEGORY NAV MENU


//echo'<a href="http://www.hawaiinightlifeguide.com/"><img src="/img/hawaiinightlifeguide.png" alt="Hawaii Nightlife Guide" border="none"></a><br><br>';

echo'<div id="addThis">',
	'<!-- AddThis Button BEGIN -->',
	'<a class="addthis_button" href="http://www.addthis.com/bookmark.php?v=250&amp;pub=xa-4af7654a240e4b22" rel="nofollow">',
	'<img src="http://s7.addthis.com/static/btn/v2/lg-share-en.gif" width="120" height="16" alt="Bookmark and Share" style="border:0;"></a>',
	'<script type="text/javascript" src="http://s7.addthis.com/js/250/addthis_widget.js#pub=xa-4af7654a240e4b22"></script>',
	'<!-- AddThis Button END -->',
	'</div>';

// BEGIN OTHER LEFT LINKS
echo
'<div id="leftlinks">'."\n",

'<span><a href="/submit.php">Add Your Site</a></span>'."\n",
'<span><a href="/suggest_site.php">Suggest A Site</a></span>'."\n",
//'<span><a href="/tag_sphere.php">Hawaii Tag Sphere</a></span>'."\n",
'<span><a href="/contact.php">Contact Us</a></span>'."\n",
'<span><a href="/link_code.php">Link to Us</a></span>'."\n",
'<span><a href="/submit_news.php">Submit News Story</a></span>'."\n",

'<span><a href="/Hawaii_Bookstore.php">Hawaii Bookstore</a></span>'."\n",
'<span><a href="/inter-island_travel.php">Inter-island Travel</a></span>'."\n",
'<span><a href="/hawaii_websites.php">Hawaii Websites</a></span>'."\n",
'<span><a href="/adventure_travel_resources.php">Adventure Travel Sites</a></span>'."\n",

'<span><a href="/webmasters.php">Webmaster Info</a></span>'."\n",
'<span><a href="/hawaii_web_designers.php">Web Designers</a></span>'."\n",
'<span><a href="/sitemap.php" style="margin-bottom:0;">Sitemap</a></span>'."\n",
'</div>'."\n";
// END OTHER LEFT LINKS


/*
if ($pagetype=='category') $listlength=($listlength - 3);
if ($pagetype=='listing') $listlength=7;
if ($page=='Book_Hostels') $listlength=7;
//if ( ($listlength > 4 ) and ($listlength < 8) ) leftColumnShort();
if ($listlength > 4)leftColumnTall();
*/
leftColumnTall();

//END LEFTNAV
echo'</div>'."\n";

//END LEFT COLUMN
echo'</div>'."\n";

//BEGIN RIGHT
if ( ($page != 'Hawaii_Bookstore') and ($page != 'Book_Hostels') ) {
 
	echo
	'<div id="right" class="column" align="center">'."\n";

	if ($errorstring)echo '<div id="error" align="center"><h3>SUBMISSION ERRORS:</h3>'.$errorstring.'</div>'."\n";
	
	if ($pagetype=='listing'){
	
		$cityquery="SELECT * FROM tblcities WHERE cityname='$city' ";
		$cityresult=mysql_query($cityquery);
		$fkcities=@mysql_result($cityresult,0,pkcities);
		$currentlistingurl=$domain.$cwd;
		
		$query="SELECT * FROM tbllistings WHERE athurl = '$currentlistingurl' ";
		$result=mysql_query($query);
		
		$pklistings =mysql_result($result,0,"pklistings");
		$weburl =mysql_result($result,0,"weburl");
		$fkcategories=mysql_result($result,0,"fkcategories");
		$isfeatured=mysql_result($result,0,"isfeatured");
		$hbcode=mysql_result($result,0,"hbcode");
		$webimg=$img;
		
		if ($weburl){
			
			if (!$isfeatured)$nofollow='rel="nofollow"';
			else $nofollow='';
			
			echo 
			'<h3 ><a href="http://'.$weburl.'" target="new" '.$nofollow.' onClick="javascript:urchinTracker(\'/external/'.str_replace(".", "_", $weburl).'\');" style="font-size:60%;">',
			$listingname.'</a></h3>',
			'<div id="listingblock"  align="center">'."\n",
			'<img src="/img/'.$webimg.'.gif" style="margin:0;" alt="'.$weburl.'">'."\n";
			
			if ($hbcode > 0){
				echo
				'<FORM style="margin:0; padding:5px;" action="/Book_Hostels.php" method="post" >'."\n",
				'<input type="hidden" name="hbcode" value="'.$hbcode.'">'."\n",
				'<input type="submit" value=" Book Now ">'."\n",
				'</form>'."\n";
			}
			
			if ($bookingenabled){
				echo
				'<FORM style="margin:0; padding:5px;" action="http://'.$bookingurl.'" enctype="multipart/form-data" method="get" >'."\n",
				'<input type="hidden" name="referedby" value="'.$domain.'">'."\n",
				'<input type="submit" value=" Book Now" onClick="javascript:urchinTracker(\'/external/'.str_replace(".", "_", $bookingurl).'\');">'."\n",
				'</form>'."\n";
			}
			
			
			echo'</div>'."\n";
		}else{
			if ($hbcode > 0){
				echo
				'<img src="/img/noweb_hbc.png"><br>',
				'<FORM style="margin:0; padding:5px;" action="/Book_Hostels.php" method="post" >'."\n",
				'<input type="hidden" name="hbcode" value="'.$hbcode.'">'."\n",
				'<input type="submit" value=" Book Now ">'."\n",
				'</form>'."\n";
			}
		}
		
	}
	
	?>	<h3 class="fontten">Follow Adventure Travel Hawaii</h3>
        <div class="follow">
        <a href="http://www.facebook.com/pages/Adventure-Travel-Hawaii/328757255385" title="Follow Adventure Travel Hawaii on Facebook"><img src="/img/follow_on_facebook.png" alt="Follow Adventure Travel Hawaii on Facebook" ></a>
        <a href="http://twitter.com/athawaii"  title="Follow Adventure Travel Hawaii on Twitter"><img src="/img/follow_on_twitter.png" alt="Follow Adventure Travel Hawaii on Twitter" ></a>
        <a href="http://friendfeed.com/athawaii"  title="Follow Adventure Travel Hawaii on Friendfeed"><img src="/img/follow_on_friend_feed.png" alt="Follow Adventure Travel Hawaii on Friendfeed" ></a>
        <a href="http://www.Adventure-Travel-Hawaii.com/rss/" title="Follow Adventure Travel Hawaii RSS Updates"><img src="/img/follow_rss.png" alt="Follow Adventure Travel Hawaii Updates RSS" ></a>
        <a href="http://adventuretravelhawaii.blogspot.com/"  title="Follow The Adventure Travel Hawaii Blog"><img src="/img/follow_on_blogspot.png" alt="Follow The Adventure Travel Hawaii Blog" ></a>
        <a href="http://www.Adventure-Travel-Hawaii.com/rss/news.php"  title="Follow Hawaii Adventure Travel News Articles"><img src="/img/follow_rss.png" alt="Follow Hawaii Adventure Travel News Articles" ></a>
        </div>

	<?php
	
	if ($pagetype!='listing'){
		//BEGIN FEATURED
		if  ($pagetype!='unique')include '_featured.inc'; 
		if  ($page=='Quickresults') include '_featured_result.inc'; 
		// END FEATURED 
		
		//if ($pagetype='island')include '_island_books.inc';
		
	}

		/*
	if ( ($page != 'Tag_index') and ($page != 'Tag_sphere') )include '_tags.inc';		
	*/
	include '_news.inc';
	
	echo 
		'<h3 >Hawaii Bookstore</h3>',
		'<div id="bookstore">',
		'<a href="/Hawaii_Bookstore.php" >',
		'<img src="/img/hawaii_bookstore.png" border="0"  alt="Hawaii Bookstore" title="Hawaii Bookstore" >',
		'</a>',
		'</div>';
		
	
	//BEGIN WEATHER DISPLAY
	if (($pagetype=='city')or($pagetype=='listing')or(($page=='Quickresults')and($city))or(($pagetype=='category')and($city))){
		if ($city=='Kona')$weathercity='Kailua_Kona';
		else $weathercity=$city;
		echo
		'<h3 >'.$city.' Weather</h3>',
		'<div id="w3" >',
		'<a href="http://www.wunderground.com/US/HI/'.$weathercity.'.html?bannertypeclick=infoboxtr" target="new" rel="nofollow" onClick="javascript:urchinTracker(\'/external/wunderground_com_'.$weathercity.'\');">',
		'<img src="http://banners.wunderground.com/weathersticker/infoboxtr_both/language/www/US/HI/'.$weathercity.'.gif" border=0 alt="Click for '.$weathercity.', Hawaii Forecast" >',
		'</a></div>';
	}
	// END WEATHER DISPLAY

	////////////////////////////////////////////////////////////////////////// INSERT ADVERTISING INCLUDE
	//if ($listlength > 6)include'flashad.inc'; ///////////// SKYSCRAPER BANNER ADS
	////////////////////////////////////////////////////////////////////////// SIZE 160 X 600 
	
	echo
	'</div>'."\n";
	//END RIGHT
}
echo '<p style="clear:both;">&nbsp;</p></div>';
//END MAIN

echo'<div id="topbar">';
//topsearch();
toplogin($currentusrname, $pagetype, $pklistings, $subname);
echo'</div>';

// BEGIN HOME PAGE BUTTON
/*
echo 
'<div id="home">'."\n",
'<span><a title="Adventure Travel Hawaii Home" href="http://'.$domain.'">Hawaii Travel</a></span>'."\n",
'</div>'."\n";
*/
// END HOME PAGE BUTTON

// BEGIN SUBMIT SITE BUTTON
echo
'<div id="submitbutton">'."\n",
'<span><a href="/submit.php">Add Your Site</a></span>'."\n",
'</div>'."\n";
//END SUBMIT SITE BUTTON

//BEGIN HAWAII NET DEVELOPERS LOGO
echo '<a id="hawaii_net_developers" href="http://www.hawaiinetdevelopers.com">'."\n",
	 '<img src="/img/hawaii-net-developers.jpg" alt="Website design by Hawaii Net Developers">'."\n",
	 '</a>'."\n";
//END HAWAII NET DEVELOPERS LOGO

$lastupdate=get_site_updated();
echo 
	'</div>',
	'<p style="clear:both;width:100%;text-align:center;font-size:12px;margin:0;padding:7px 0 7px 0;">',
	'Copyright &copy; 2009 <a href="/">'.$domain.'</a> - <a href="http://www.hawaiinetdevelopers.com">Website updated by Hawaii Net Developers</a> '.$lastupdate.'</p>'."\n";


/*
<!-- Start Quantcast tag -->
<script type="text/javascript">
//_qoptions={
//qacct:"p-c1RJYssKicgLM"
//};
</script>

<script type="text/javascript" src="http://edge.quantserve.com/quant.js"></script>
<noscript>
<img src="http://pixel.quantserve.com/pixel/p-c1RJYssKicgLM.gif" style="display: none;" border="0" height="1" width="1" alt="Quantcast">
</noscript>
<!-- End Quantcast tag -->
*/
?>
<script type="text/javascript">
var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
</script>

<script type="text/javascript">
var pageTracker = _gat._getTracker("UA-4541026-1");
pageTracker._initData();
pageTracker._trackPageview();
</script>

<?php
echo'</body></html>';
mysql_close();
?>