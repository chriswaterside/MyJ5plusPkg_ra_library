var ra;
displayCustomValues = function ($option, $walk) {
    var $response = {
        found: true,
        out: ""};
    // custom field must start with x
    switch ($option) {
        case "{xdowddmm}":
            $response.out = "<b>" + ra.date.dow($walk.basics.walkDate) + "<br/>" + ra.date.ddmm($walk.basics.walkDate) + $walk.basics.addYear() + "</b>";
            break;
        case "{xSymbol}":
            /* Picnic or Pub icon */
            if ($walk.basics.additionalNotes.includes("picnic")) {
                $response.out = '<img src="' + ra.baseDirectory() + 'media/com_ra_library/js/jsonwalks/sr02/Sandwich-icon.png" title="Picnic Required" width="24" height="24" align="left"/>';
                break;
            }
            if ($walk.basics.additionalNotes.includes("pub")) {
                $response.out = '<img src="' + ra.baseDirectory() + 'media/com_ra_library/js/jsonwalks/sr02/beer.png" title="Pub Lunch" width="24" height="24" align="left"/>';
            }
            break;
        case "{xNationalGrade}":
            $response.out = $walk.getIntValue("walks","_nationalGrade").toUpperCase();
            break;
        case "{xContact}":
            $response.out = "<b>" + $walk.getIntValue("contacts","contactName") + "</b>";
            break;
        case "{xGradeImg}":
            $response.out = gradeImage($walk.getIntValue("walks","nationalGrade"));
            break;
        default:
            $response.found = false;
            break;
    }

    return $response;
};
gradeImage = function (nationalGrade) {

    var $url = ra.baseDirectory() + "media/com_ra_library/js/jsonwalks/sr02/images/grades/";
    switch (nationalGrade) {
        case "Event":
            $url = "<img src='" + $url + "event.jpg' alt='Event' height='30' width='30'>";
            break;
       case "Easy Access":
            $url = "<img src='" + $url + "grade-ea30.jpg' alt='Easy Access' height='30' width='30'>";
            break;
        case "Easy":
            $url = "<img src='" + $url + "grade-e30.jpg' alt='Easy' height='30' width='30'>";
            break;
        case "Leisurely":
            $url = "<img src='" + $url + "grade-l30.jpg' alt='Leisurely' height='30' width='30'>";
            break;
        case "Moderate":
            $url = "<img src='" + $url + "grade-m30.jpg' alt='Moderate' height='30' width='30'>";
            break;
        case "Strenuous":
            $url = "<img src='" + $url + "grade-s30.jpg' alt='Strenuous' height='30' width='30'>";
            break;
        case "Technical":
            $url = "<img src='" + $url + "grade-t30.jpg' alt='Technical' height='30' width='30'>";
            break;
    }
    return $url;
};

displayGradesRowClass = function ($walk) {
    return displayTableRowClass($walk);
};
displayListRowClass = function ($walk) {
    return displayTableRowClass($walk);
};

displayTableRowClass = function ($walk) {
    var $class = "leisurely";
    var $day = $walk.getIntValue("basics", "dayofweek");
    if (($walk.getIntValue("walks", "shape")==="Linear") && ($day === "Wednesday")) {
        $class = "sr02linear";
    } else {
        switch ($walk.getIntValue("walks", "_nationalGrade")) {
            case "Easy" :
                $class = "sr02easy";
                break;
            case "Leisurely" :
                $class = "sr02leisurely";
                break;
            case "Moderate" :
                $class = "sr02moderate";
                break;
            case "Strenuous" :
                $class = "sr02strenuous";
                break;
        }
    }
    return $class;
};