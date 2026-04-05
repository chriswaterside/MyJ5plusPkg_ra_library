var ra;
if (typeof (ra) === "undefined") {
    ra = {};
}
if (typeof (ra) === "undefined") {
    ra.walkseditor = {};
}
ra.walkseditor.help = (function () {
    var help = {};
    help.locateButtons = "<p>You can also specify a postcode and display previously used Meeting and Starting places</p>";
    help.editButtons = function () {
        return '<h4>Status</h4><p>The walk can be defined as Draft, Awaiting Approval, Published or Cancelled. Which options you can see depends on your authority and if there are outstanding issues with the walk.</p>' +
                'Published and Cancelled walks are viewable by the public and are displayed within the Group\'s walks programme' +
                '<h4>Category</h4><p>If this is shown then you are able to categorise the walk, using the categories defined by your group.</p>' +
                '<h4>Save</h4><p>Save the walk and close the edit function</p>' +
                '<h4>Cancel</h4><p>Close the edit function without saving, any changes will be lost</p>' +
                '<p><b>Note: Failing to either Save or Close the walk may result in locking others from editing the walk</b></p>' +
                '<h4>Preview</h4><p>Display a popup showing roughly how the walk would be displayed to the users, plus any outstanding issues</p>';
    };
    help.editQuestion = function () {
        return ('This is a published or cancelled walk that is viewable by the public\r\n\r\nAre you sure you wish to edit this walk?');
    };
    help.deleteQuestion = function () {
        return ('This is a published or cancelled walk that is viewable by the public\r\n\r\nAre you sure you wish to delete this walk?');
    };
    help.locationMeet = function () {
        var out = "Position the marker to indicate the meeting place for the walk.<br>You can <ul><li>Drag the marker to the correct location</li><li>Use the <b>Move Marker</b> option to move the marker to a specific place</li></ul>";
        out += help.locateButtons;
        return out;
    };
    help.locationStart = function () {
        var out = "Position the marker to indicate the start of the walk.<br>You can <ul><li>Drag the marker to the correct location</li><li>Use the <b>Move Marker</b> option to move the marker to a specific place</li></ul>";
        out += help.locateButtons;
        return out;
    };
    help.locationArea = function () {
        return "Position the marker to indicate the general area of the walk.<br>You can <ul><li>Drag the marker to the correct location</li><li>Use the <b>Move Marker</b> option to move the marker to a specific place</li></ul>";
    };

    help.locationPostcode = function () {
        return "Use this option if you wish to publish a postcode to aid walkers with their Satnav.<br/>The option will either <ul><li>display postcodes near to the marker, select the most appropriate postcode by clicking on it.<br/>NOTE: the postcodes may take a few seconds to appear</li><li>or if a postcode has been defined it will ask if you wish to delete it</li></ul>";
    };
    help.locationPlaces = function () {
        return "This option displays Meeting / Start locations that have been used by Ramblers Groups in the past.<br/>It displays a number of locations around the position of the marker. <br/>Some of these may help you locate a suitable location.";
    };
    help.locationSearch = function () {
        return "You can find a location, and move the marker, on the map by entering<ul><li>an OS Grid Reference, of any length</li><li>a post code</li><li>a road or place name with the town or county You may qualify a road or place name e.g. Bulls Head, Foolow or London Road, Derby. </li><li>You may also specify a location using What3Words, e.g for the summit of Snowden ///super.ultra.enhancement</li></ul>";
    };
    help.basicDate = function () {
        return "Type in Date in dd/mm/yyyy format or click icon and select date from calendar that comes up";
    };
    help.basicTitle = function () {
        return "Choose an eye-catching short title that includes information about the location of the walk.";
    };
    help.basicDesc = function () {
        return "Describe what walkers can expect from this walk<ul>" +
                "<li>Be friendly, positive and welcoming – use first person e.g. We will…/Join us for…</li>" +
                "<li>Include some highlights – are there any good views or points of interest?</li>" +
                "<li>Let people know what to expect – for example how flat/hilly the walk is, the conditions underfoot and the pace of the group</li>" +
                "<li>Avoid acronyms and abbreviations – new walkers may not know what you mean!</li>" +
                "<li>Include information about toilet facilities and refreshment stops</li></ul>";
    };
    help.basicNotes = function () {
        return "This Additional notes field is intended to contain extra information about the walk. It is displayed when the users displays the complete details of the  walk." +
                "<br/>It can also be used to contain extra data the group wish to display on their site against a walk. In this case it can be included in the table or list views of the walks. The web master would need to have customised the table and list views.";
    };
    help.meetType = function () {
        return "Select from drop down<br/>Specify the option that best describes your walk, remember you also have the option of specifying either the start of the walk, where you put your boots on and walk or the general area for the walk.<br/>Use the later if you do not wish walkers to travel directly to the start or they need to contact the group to do this. ";
    };
    help.meetTime = function () {
        return "Type in meeting time or click icon to select from drop down";
    };
    help.meetPredefined = function () {
        return "meet predefined";
    };
    help.meetName = function () {
        return "Type in name or general description of meeting place to aid people finding it, e.g. Darley Park Drive cp<br/>You must also specfy the location on the map by positioning the marker on the map";
    };
    help.areaTime = function () {
        return "As you have decided to just publish the general area of the walk you may not wish to say the exact time the walk starts. However there are cases when you may wish to let people know when the walk starts but not exactly where, In that case type in start time or click icon to select from drop down";
    };
    help.startType = function () {
        return "Select whether the start will be published as a specific location in which case walkers can travel independently to the start of the walk or just a general area for the walk in which case anyone wanting to go to start will need to contact the walk organiser / leader for more details<br/>If you meet up and travel to the start of the walk together, then specify this using the Meeting option";
    };
    help.startName = function () {
        return "Type in name or general description of start place to aid people finding it,e.g. Ladybower Visitors Centre<br/> You must also specfy the location on the map by positioning the marker on the map";
    };
    help.startArea = function () {
        return "Give the name of the general area for the walk, so walkers know where they are going. e.g. Brassington area";
    };
    help.startTime = function () {
        return "Type in start time or click icon to select from drop down";
    };
    help.finishTime = function () {
        return "Estimate of when the walks is likely to finish";
    };
    help.finishName = function () {
        return "Type in name or general description of finish place to aid people finding it.<br/>This is only needed for <b>Linear</b> walks<br/>You must also specfy the location on the map by positioning the marker on the map";
    };
    help.walkDistance = function () {
        return "Type in numeric distance (to one dec place)";
    };
    help.walkUnits = function () {
        return "Select miles or kilometres from drop down";
    };
    help.walkType = function () {
        return "Select Circular, Linear or Figure of 8 from drop down";
    };
    help.walkNatGrade = function () {
        return "Select National Grade from drop down";
    };
    help.walkLeader = function () {
        return "If Walk Leader is different from Contact listed lower down then enter it here as you would like it displayed (optional)";
    };
    help.walkLocalGrade = function () {
        return "Select local grade from drop down";
    };
    help.walkLocalGradeText = function () {
        return "Enter the grade as per your groups grading system";
    };
    help.walkPace = function () {
        return "Enter optional pace of the walk, in line with how the group normally describe pace.";
    };
    help.walkAscent = function () {
        return "Total ascent over whole walk. Ignore descent, otherwise circular walks will always be zero! (Optional)<br/>This is a text field so please indicate if the ascent is in feet or metres.";
    };
    help.walkDuration = function () {
        return "Estimate of likely time duration of walk (optional)";
    };
    help.contactName = function () {
        return "Enter name as you would like it displayed, e.g. Chris V, first name and initial of surname<br>If the walk is to be uploaded to Walks Manager then your surname is required";
    };
    help.contactEmail = function () {
        return "If you wish people to ask via email about the walk then enter an email address. NOTE: the email address will not be displayed via the group web site or seen by the public, it will only be used to send the email.(Optional)";
    };
    help.contactTel1 = function () {
        return "Enter telephone number of contact (Optional)";
    };
    help.contactTel2 = function () {
        return "Enter alternative telephone number of contact (optional)";
    };
    help.contactType = function () {
        return "Choose from drop down menu<br/>If the contact is not the leader then you can optionally specify who the leader is under the Walk section (distance, grade etc)";
    };
    help.contactPredefined = function () {
        return "Click to search for a contact that has been saved on the system";
    };
    help.contactID = function () {
        return "This field is used to specify the Contact ID required when you upload the walk to GWEM";
    };
    help.editorNotes = function () {
        return "You may record any future changes that will be required or additional information required, before the walk can be published. This information is for the editor's use only.";
    };
    help.formOptions = function () {
        return  '<h4>Preview walk</h4><p>View the walk as a whole to review the information you have entered</p>' +
                '<h4>Email walk to group</h4><p>Email the walk to your group\'s Programme Secretary/Walks coordinators</p>' +
                '<h4>Save walk</h4><p>Save the walk so you can upload it later and add additional information.</p>' +
                '<h4>Upload walk</h4><p>Upload a previously saved walk, so you can change or add information</p>' +
                '<p>Please note that any existing information in the form will be overwritten.</p>';
    };
    help.programmeOptions = function () {
        return  '<h4>Preview walk</h4><p>View the walk as a whole to review the information you have entered</p>' +
                '<h4>Save walk</h4><p>Save the walk so you can upload it later and add additional information.</p>' +
                '<h4>Upload walk</h4><p>Upload a previously saved walk, so you can change or add information</p>' +
                '<p>Please note that any existing information in the form will be overwritten.</p>' +
                '<h4>WM Export</h4><p>Save the walk in a CSV file suitable for uploading to Walks Manager</p>';
    };

    return help;
}
());