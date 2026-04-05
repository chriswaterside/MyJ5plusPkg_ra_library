/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
var document;
var L, ra;
if (typeof (ra) === "undefined") {
    ra = {};
}
if (typeof (ra.walkseditor) === "undefined") {
    ra.walkseditor = {};
}
if (typeof (ra.walkseditor.form) === "undefined") {
    ra.walkseditor.form = {};
}

ra.walkseditor.form.submitwalk = function (options, data) {
    //  this.emailURL = ra.baseDirectory() + "media/com_ra_library/js/walkseditor/sendemail.php";
    this.data = data;
    this.groups = this.data.groups;
    this.localGrades = this.data.localGrades;
    this.email = {name: '',
        email: '',
        message: ''
    };
    this.programme = new ra.walkseditor.walks();
    this.walk = null; // object rather than just the data

    this.load = function () {
        this.walk = new ra.walkseditor.walk();
        this.walk.init("Draft", "", false);
        this.programme.addWalk(this.walk);
        var tag = document.getElementById(options.divId);
        var topOptions = document.createElement('div');
        topOptions.setAttribute('class', 'ra-edit-options');
        tag.appendChild(topOptions);
        this.addButtons(topOptions);
        var clear = document.createElement('div');
        clear.setAttribute('class', 'clear');
        tag.appendChild(clear);

        this.editorDiv = document.createElement('div');
        tag.appendChild(this.editorDiv);
        var editor = new ra.walkseditor.walkeditor();
        // editor.sortData();
        editor.setGroups(this.groups);
        editor.setLocalGrades(this.localGrades);
        editor.load(this.editorDiv, this.walk.data, true);

    };
    this.addButtons = function (tag) {

        var _this = this;
        var helpSpan = document.createElement('div');
        helpSpan.style.cssFloat = "right";
        tag.appendChild(helpSpan);
        new ra.help(helpSpan, ra.walkseditor.help.formOptions).add();

        var previewButton = document.createElement('button');
        previewButton.textContent = 'Preview walk';
        previewButton.title = 'Preview walk and see outstanding issues';
        previewButton.setAttribute('class', 'ra-button');
        previewButton.addEventListener("click", function () {
            _this.walk.previewWalk();
        });
        tag.appendChild(previewButton);

        var emailButton = document.createElement('button');
        emailButton.setAttribute('type', 'button');
        emailButton.setAttribute('class', 'ra-button');
        emailButton.textContent = "Email walk to group";
        emailButton.title = "Email walk to the Groups' Programme Sec/Walks co-ordinators";
        emailButton.addEventListener("click", function () {
            _this.emailModal();
        });
        tag.appendChild(emailButton);

        var saveButton = document.createElement('button');
        saveButton.setAttribute('type', 'button');
        saveButton.setAttribute('class', 'ra-button');
        saveButton.textContent = "Save walk";
        saveButton.title = "Save walk to PC to update later";
        saveButton.addEventListener("click", function (e) {
            _this._saveWalk();
        });
        tag.appendChild(saveButton);

        var uploadButton = document.createElement('button');
        // uploadButton.setAttribute('type', 'button');
        uploadButton.setAttribute('class', 'ra-button');
        uploadButton.textContent = "Read walk";
        uploadButton.title = "Read/Upload previously saved walk";
        tag.appendChild(uploadButton);

        var upload = new ra.uploadFile();
        upload.addField(uploadButton, ".walks,.json");
        uploadButton.addEventListener("upload-file-read", function (e) {
            var okay = ra.showConfirm("Any existing data entered will be overwritten, Confirm action");
            if (!okay) {
                return;
            }
            var walks = {};
            try {
                var walks = JSON.parse(e.ra.result);
            } catch (e) {
                ra.showError(e, 'Error reading file');
                return;
            }
            var readWalk = null;
            if (Array.isArray(walks)) {
                switch (walks.length) {
                    case 0:
                        ra.showMsg("No walks found in file");
                        return;
                    case 1:
                        readWalk = walks[0];
                        break;
                    default:
                        readWalk = walks[0];
                        ra.showMsg("More than one walk in upload file, only the first walk has been read");
                }
            } else {
                readWalk = walks;
            }

            var newWalk = new ra.walkseditor.walk();
            newWalk.createFromObject(readWalk);
            _this.programme.clearWalks();
            _this.programme.addWalk(newWalk);
            _this.walk = newWalk;
            _this.editorDiv.innerHTML = '';
            var editor = new ra.walkseditor.walkeditor();
            //   editor.sortData();
            editor.setGroups(_this.groups);
            editor.setLocalGrades(_this.localGrades);
            editor.load(_this.editorDiv, newWalk.data, true);

        });

        return tag;
    };
    this.emailModal = function () {
        var input = new ra.walkseditor.inputFields;
        var form = document.createElement('form');
        var _this = this;
        var message = 'Send walk details to the walks co-ordinator';
        input.addHeader(form, 'h2', message);
        input.addComment(form, '', '', 'Provide any message you wish to send the walk coordinator and tell them who you are and your email address so they can contact you about the walk');
        input.addTextArea(form, 'divClass', 'Message', 8, this.email, 'message', 'Your message to the walks coordinator');
        input.addText(form, 'divClass', 'Your name', this.email, 'name', 'Your name');
        input.addEmail(form, 'divClass', 'Your email', this.email, 'email', 'Your email address');
        var messageDiv = document.createElement('div');
        form.appendChild(messageDiv);
        input.addComment(form, '', '', 'The details of the walk will be added to the email, together with an attachment containing the walk stored in JSON format');
        var emailButton = document.createElement('button');
        emailButton.setAttribute('type', 'button');
        emailButton.setAttribute('class', 'link-button mintcake');
        emailButton.textContent = "Submit";
        emailButton.addEventListener("click", function () {
            if (_this.email.message === "" || _this.email.name === "" || _this.email.email === "") {
                messageDiv.innerHTML = "You must specify your name, email address and provide a message";
            } else {
                messageDiv.innerHTML = "";
                _this.modal.setContent("Sending email ...", false, false);
                _this._emailWalk(_this.modal);
            }

        });
        form.appendChild(emailButton);
        this.modal = ra.modals.createModal(form, false);
    };
    this._emailWalk = function (modal) {
        //var $url = this.emailURL;
        //var self = this;
        //var fromSite = window.location.href;
        var date = ra.getObjProperty(this.walk.data, "basics.date", "");
        var title = ra.getObjProperty(this.walk.data, "basics.title", "");
        var filename = 'walk' + date + title + '.json';
        var data = {
            toid: this.data.coords,
            replyTo: {'name': this.email.name,
                'email': this.email.email},
            copy: {'name': this.email.name,
                'email': this.email.email},
            title: 'Submit walk to the walks co-ordinator',
            content: '<p>To Walks coordinators, copy: ' + this.email.name + '</p>' + this.email.message,
            attach: {
                data: JSON.stringify([this.walk.data], null, "    "),
                type: 'string',
                encoding: 'base64',
                filename: filename,
                mimeType: 'application/json'
            }

        };
        this.serverAction(this, '', data, (self, results) => {
            modal.close();
        });
    };

    this._saveWalk = function () {
        var walks = [];
        walks.push(this.walk.data);
        var data = JSON.stringify(walks, null, "    ");
        try {
            var blob = new Blob([data], {type: "application/json"});
            // add date to file name??
            name = "groupwalk.walks";
            saveAs(blob, name);
        } catch (e) {
            ra.showError('Your web browser does not support his option!');
        }
    };
    this.serverAction = function (self, action, dataObj, fcn) {
        var url;
        if (dataObj === null) {
            dataObj = {noInput: true};
        }
        url = "index.php?option=com_ra_library&view=sendemail&format=json";

        url = ra.baseDirectory() + url;
        var formData = new FormData();
        $data = JSON.stringify(dataObj);
        formData.append("data", $data);
        //  formData.append("data", md5($data));
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onload = function () {
            if (xmlhttp.readyState === 4) {
                const response = JSON.parse(xmlhttp.responseText);
                response.status = xmlhttp.status;
                if (response.status === 200) {
                    self.displayFeedback(response.data.feedback);
                    fcn(self, response);
                } else {
                    ra.showMsg('Whoops - something went wrong [' + action + ']: ' + response.message);
                }
            }
        };
        xmlhttp.open("POST", url, true);
        xmlhttp.send(formData);
    };
    this.displayFeedback = function (feedback) {
        if (feedback === null) {
            ra.showMsg('Invalid response from server, feedback is null');
            return;
        }
        if (feedback.length < 1) {
            return;
        }
        var div = document.createElement("div");
        div.classList.add('ra');
        div.classList.add('booking');
        div.classList.add('feedback');
        div.style.display = "inline-block";
        ra.modals.createModal(div, false);
        if (typeof feedback === 'string') {
            var div1 = document.createElement("div");
            div1.innerHTML = feedback;
            div.appendChild(div1);
            return;
        }
        // array
        feedback.forEach(item => {
            var div1 = document.createElement("div");
            div1.innerHTML = item;
            div.appendChild(div1);
        });
    };
};