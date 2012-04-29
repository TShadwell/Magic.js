<?PHP
function addquotes($s) {
        return ("\"".$s."\"");
}
function associative_push($key,$val,$a){
        $a[$key]=$val;
        return $a;
}
function JSON_array($arr){
        return "[" . implode(", ", $arr) . "]";
}
function JSON_arrayify($val){
        $smithereens=explode(" ", $val);
        $smithereens[1]="\"".$smithereens[1]."\"";
        return "[" . implode(", ", $smithereens ) . "]";
} 
if(isset($_GET['source'])){ 
    $lines = implode(range(1, count(file(__FILE__))), '<br />'); 
    $content = highlight_file(__FILE__, TRUE); 
    die('<!DOCTYPE HTML><html><head><title>Page Source For: '.__FILE__.'</title><style type="text/css">body {margin: 0px;margin-left: 5px;}.num {border-right: 1px solid;color: gray;float: left;font-family: monospace;font-size: 13px;margin-right: 6pt;padding-right: 6pt;text-align: right;}code {white-space: nowrap;}td {vertical-align: top;}</style></head><body><table><tr><td class="num"  style="border-left:thin; border-color:#000;">'.$lines.'</td><td class="content">'.$content.'</td></tr></table></body></html>'); 
}
//Name
$name=exec('hostname');
//Uptime, total uptime, load avgs
preg_match("/ +(\d+(?::\d+)+) +up +(.+),.+, +load average: (\d+\.\d+), (\d+\.\d+), (\d+\.\d+)/", exec('uptime'), $uptime);
$thisup=$uptime[2];
$totalup=$uptime[1];
$loadavgs=JSON_array(array($uptime[3], $uptime[4], $uptime[5]));
//Users
$seen=array();
exec('who',$who);
foreach ($who as $line){
        $wds=explode(" ", $line);
        if (!in_array($wds[0], $seen)){
                array_push($seen,$wds[0]);
        }
}
//Network related stats
$vnstat=explode(";",exec('vnstat --oneline'));
$vnstats=array(
        "version"=>$vnstat[0],
        "interface"=>$vnstat[1],
        "timestamp"=>$vnstat[2],
        "day"=> array_map(
                "JSON_arrayify",
                array(
                        "recieved"=>$vnstat[3],
                        "transmitted"=>$vnstat[4],
                        "total"=>$vnstat[5],
                        "avg"=>$vnstat[6]
                )
        ),
        "month"=> associative_push(
                "timestamp",
                $vnstat[7],
                array_map(
                        "JSON_arrayify",
                        array(
                                "recieved"=>$vnstat[8],   
                                "transmitted"=>$vnstat[9],
                                "total"=>$vnstat[10],
                                "avg"=>$vnstat[11]
                        )
                )
        ),
        "all time"=> array_map(                                      
                        "JSON_arrayify",
                        array(
                                "recieved"=>$vnstat[12],
                                "transmitted"=>$vnstat[13],
                                "total"=>$vnstat[14]
                                )
                )
);
//Sensors stuff
exec('sensors', $sens);
$sensors=array();
foreach ($sens as $line){
        if (!(strpos($line, ")")===false)){
                $o=explode("(",$line);
                $val=explode(":",str_replace(" ","",$o[0]));
                $sensors[$val[0]]=$val[1];
        }
}
$chassisfans=array_map(
        function($str){
                return str_replace("RPM", "", $str);
        },
        array($sensors["fan4"],$sensors["fan3"],$sensors["fan1"])
);
$cputemp=str_replace(array("C","+"),"",$sensors["Physicalid0"]);
//uname CPU stuff
$o=explode(" @ ",str_replace(array("GHz", "CPU"), "" ,exec('uname -p')));
$cpuname=$o[0];
$cpuclock=$o[1];
//GPU stuff
$gputemp = exec('nvidia-smi -q -d TEMPERATURE | grep Gpu | cut -c35-36');
?>
{
        "name":"<?PHP echo $name ?>",
        "time":"<?php echo $totalup?>",
        "uptime":"<?php echo $thisup?>",
        "load":<?PHP echo $loadavgs?>,
        "users":[<?php echo implode(", ", array_map("addquotes",$seen))?>],
        "os":
        {
                "kernel":"<?PHP echo exec("uname -r") ?>"
        },
        "network":
        {
                "month":
                {
                        "recieved":<?php echo $vnstats["month"]["recieved"]?>,
                        "total":<?php echo $vnstats["month"]["total"]?>,
                        "transmitted":<?php echo $vnstats["month"]["transmitted"]?>
                
                },
                "day":
                {
                        "recieved":<?php echo $vnstats["day"]["recieved"]?>,
                        "total":<?php echo $vnstats["day"]["total"]?>,
                        "transmitted":<?php echo $vnstats["day"]["transmitted"]?>

                },
                "all time":
                {
                        "recieved":<?php echo $vnstats["all time"]["recieved"]?>,
                        "total":<?php echo $vnstats["all time"]["total"]?>,
                        "transmitted":<?php echo $vnstats["all time"]["transmitted"]?>      
                }
        },
        "fans":
        {
                "side":<?php echo $chassisfans[0]?>,
                "top":<?php echo $chassisfans[1]?>,
                "rear":<?php echo $chassisfans[2]?>

        },
        "cpu":
        {
                "name":"<?PHP echo $cpuname?>",
                "temperature":<?PHP echo $cputemp?>,
                "clock":<?PHP echo $cpuclock?>

        },
        "gpu":
        {
                "name":"Nvidia GeForce GTX 570",
                "clock":"1.464",
                "temperature":<?php echo $gputemp?>

        },
        "magic":"<script src='magic.js'>//if you want to see an actual page, you're going to have to enable Javascript. Sorry.</script>"
}