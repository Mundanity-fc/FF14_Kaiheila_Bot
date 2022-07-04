<?php

require __DIR__ . "/vendor/autoload.php";
require __DIR__ . '/KaiheilaBot/cardMessage/Card.php';
require __DIR__ . '/KaiheilaBot/cardMessage/PlainText.php';
require __DIR__ . '/KaiheilaBot/cardMessage/ImageText.php';
require __DIR__ . '/KaiheilaBot/cardMessage/MultiColumnText.php';

use KaiheilaBot\cardMessage\Card;
use KaiheilaBot\cardMessage\ImageText;
use KaiheilaBot\cardMessage\MultiColumnText;
use KaiheilaBot\cardMessage\PlainText;
use Swlib\Saber;

function test()
{
    $saber = Saber::create([
        'base_uri' => "https://cafemaker.wakingsands.com"
    ]);
    $result = $saber->get("/quest/70000?columns=Name,Banner,TextData.ToDo,PlaceName.Name,ExperiencePoints,ItemCountReward0,ItemCountReward1,ItemCountReward2,ItemCountReward3,ItemCountReward4,ItemCountReward5,ItemCountReward6,ItemReward0,ItemReward1,ItemReward2,ItemReward3,ItemReward4,ItemReward5,ItemReward6,Icon,IssuerStart.Name,TargetEnd.Name");
    echo "\n --- \n";
    $result = json_decode($result->body, false);
    $text = json_encode($result);
    echo $text;
    echo "\n --- \n";
}

function test2()
{
    $saber = Saber::create([
        'base_uri' => "https://ff14.huijiwiki.com"
    ]);
    $result = $saber->get("/wiki/任务:晓月之终途");
    echo "\n --- \n";
    var_dump($result->getParsedDomObject()->getElementsByTagName('ul')->item(16)->textContent);
    echo "\n --- \n";
}

function test3()
{
    $conn_string = "host=localhost port=5432 dbname=FF14Bot user=postgres password=xf23652365";
    $dbconn = pg_connect($conn_string);
    $result = pg_query($dbconn, "select * from questlist where questlist.quest = '晓月之终途'");
    if (!$result) {
        echo "query did not execute";
    }
    $rs = pg_fetch_assoc($result);
    if (!$rs) {
        echo "0 records";
    }
}

function test4()
{
    $saber = Saber::create([
        'base_uri' => 'https://garlandtools.cn'
    ]);
    $result = $saber->get('/db/#quest/70000');
    echo "\n --- \n";
    var_dump($result->getParsedDomObject()->getElementsByTagName('div'));
    echo "\n --- \n";
}

function test5()
{
    $json = file_get_contents(__DIR__ . '/cardmessage.json');
    $json = json_decode($json);
    $json[0]->modules[0]->text->content = '啊对对对';
    $json[0]->modules[3]->text->fields[0]->content = $json[0]->modules[3]->text->fields[0]->content . '啊对对对';
    $arr = array('type' => 'section', 'text' => array('type' => 'plain-text', 'content' => 'addd'),);
    $arr = json_encode($arr);
    $arr = json_decode($arr);
    $a = new ImageText('a', 'https://xivapi.com/i/071000/071201.png');
    $b = new PlainText('b');
    $c = new Card();
    $c->insert($a);
    $c->insert($b);
    $d = new MultiColumnText();
    $d->insert('addd');
    $c->insert($d);
    $c = json_decode(json_encode($c));
    var_dump($c);
}

function text6()
{
    echo '';
}

\Co\run(function () {
    test5();
});
