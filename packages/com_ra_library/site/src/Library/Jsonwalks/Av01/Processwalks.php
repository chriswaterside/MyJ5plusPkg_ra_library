<?php
namespace Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Av01;

/**
 * Removes walks from feed that are not for group e.g. AV01
 * or
 * walks that contain text e.g. Wessex Wanderer Railway Walks
 * 
 * @author chris vaughan
 */
class Processwalks {

    private $includeWalksWithText = "NOT DEFINED";
    private $alwaysIncludeGroup = "";
    private $feed;

    public function __construct($feed) {
        $this->feed = $feed;
    }

    public function keepGroup($group) {
        $this->alwaysIncludeGroup = strtoupper($group);
    }

    public function keepWalkContaining($text) {
        $this->includeWalksWithText = strtolower($text);
    }

    public function process() {
        //   $walks->noWalks($this->nowalks); 
        $walks = $this->feed->getWalks();
        $items = $walks->allWalks();
        foreach ($items as $key => $walk) {
            if (strpos(strtolower($walk->title), $this->includeWalksWithText) !== false) {
                $walk->title = "Wessex Wanderer Railway Walk - " . $walk->title;
                $walk->title = $walk->title . "<span style='color: green';> [WW] </span>";
                continue;
            }
            if (strpos(strtolower($walk->description), $this->includeWalksWithText) !== false) {
                $walk->title = "Wessex Wanderer Railway Walk - " . $walk->title;
                $walk->title = $walk->title . "<span style='color: green';> [WW] </span>";
                continue;
            }
            if ($walk->groupCode === $this->alwaysIncludeGroup) {
                continue;
            }
            $walks->removeWalk($key);
        }
    }

    private function convertName($name) {

        $names = [];
        $names['Alison P'] = 'Alison P<i>arry</i>';
        $names['Andy Se'] = 'Andy Se<i>ars</i>';
        $names['Andy St'] = 'Andy St<i>eward</i>';
        $names['Ann L'] = 'Ann L<i>ight</i>';
        $names['Anna K'] = 'Anna K<i>ulisa</i>';
        $names['Ben M'] = 'Ben M<i>acKay</i>';
        $names['Beverley B'] = 'Beverley B<i>leasdale</i>';
        $names['Bill M'] = 'Bill M<i>oore</i>';
        $names['Bob M'] = 'Bob M<i>ills</i>';
        $names['Brian D'] = 'Brian D<i>rummond</i>';
        $names['Bridget G'] = 'Bridget G<i>regory</i>';
        $names['Carew R'] = 'Carew R<i>eynell</i>';
        $names['Chris D'] = 'Chris D<i>ring</i>';
        $names['Chris H'] = 'Chris H<i>olloway</i>';
        $names['Chris S'] = 'Chris S<i>anders</i>';
        $names['Clive R'] = 'Clive R<i>ichardson</i>';
        $names['Dave O'] = 'Dave O<i>sborne</i>';
        $names['David O'] = 'David O<i>sborne</i>';
        $names['David P'] = 'David P<i>ingstone</i>';
        $names['Derek B'] = 'Derek B<i>ones</i>';
        $names['Geoff D'] = 'Geoff D<i>aniels</i>';
        $names['Gill D'] = 'Gill D<i>avies</i>';
        $names['Gordon S'] = 'Gordon S<i>tillman</i>';
        $names['Heather T'] = 'Heather T<i>oyne</i>';
        $names['Hugh B'] = 'Hugh B<i>ond</i>';
        $names['Janet W'] = 'Janet W<i>ood</i>';
        $names['Jean W'] = 'Jean W<i>aller</i>';
        $names['Jill Bi'] = 'Jill Bi<i>rd</i>';
        $names['John W'] = 'John W<i>rigley</i>';
        $names['Julian B & Julie W'] = 'Julian B<i>utter</i> & Julie W<i>estgarth</i>';
        $names['Julian B'] = 'Julian B<i>utter</i>';
        $names['Julie B'] = 'Julie B<i>oston</i>';
        $names['Julie P'] = 'Julie P<i>arry</i>';
        $names['Keith B'] = 'Keith B<i>udd</i>';
        $names['Keith B'] = 'Keith B<i>udd</i>';
        $names['Maggie W'] = 'Maggie W<i>ilcox</i>';
        $names['Mandy M'] = 'Mandy M<i>acDonald</i>';
        $names['Margaret M'] = 'Margaret M<i>arsh</i>';
        $names['Margaret P'] = 'Margaret P<i>ostlethwaite</i>';
        $names['Martin S'] = 'Martin S<i>ummerell</i>';
        $names['Maureen J'] = 'Maureen J<i>ohnson</i>';
        $names['Meryl T'] = 'Meryl T<i> ill</i>';
        $names['Neil B'] = 'Neil B<i>urlton</i>';
        $names['Nigel  A'] = 'Nigel A<i>ndrews</i>';
        $names['Nigel A'] = 'Nigel A<i>ndrews</i>';
        $names['Patsy H'] = 'Patsy H<i>udson</i>';
        $names['Paula C'] = 'Paula C<i>annings</i>';
        $names['Peter G'] = 'Peter G<i>ould</i>';
        $names['Phil S'] = 'Phil S<i>elby</i>';
        $names['Richard R'] = 'Richard R<i>edding</i>';
        $names['Roger G'] = 'Roger G<i>riffiths</i>';
        $names['Rosemary D'] = 'Rosemary D<i>an</i>';
        $names['Sarah M'] = 'Sarah M<i>arshall</i>';
        $names['Sarah S'] = 'Sarah S<i>horter</i>';
        $names['Stephen D'] = 'Stephen D<i>raper</i>';
        $names['Sue Y'] = 'Sue Y<i>oung</i>';
        $names['Susan C'] = 'Susan C<i>arter</i>';
        $names['Susan J'] = 'Susan J<i>arvis</i>';
        $names['Tony K'] = 'Tony K<i>err</i>';
        $names['Tony P'] = 'Tony P<i>arsons</i>';
        $names['Wendy B'] = 'Wendy B<i>ritton</i>';
    }

}
