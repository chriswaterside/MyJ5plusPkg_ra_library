addPrintButton = function (tag) {
    var printButton = document.createElement('button');
    printButton.setAttribute('class', 'link-button tiny button mintcake right');
    printButton.textContent = 'Print';
    tag.appendChild(printButton);

    printButton.addEventListener('click', function () {
        var collection = document.getElementById("ml-printwalks");
        if (collection!==null) {
            var content = collection.innerHTML;
            ra.html.printHTML(content);
        } else {
             ra.showError("Program error");
        }

    });
};
function mlSetButtons(id) {
    var tag = document.getElementById(id);
    // create button
      addPrintButton(tag);
    var allWalks = document.createElement('button');
    allWalks.textContent = "All walks";
    allWalks.classList.add("active");
    tag.appendChild(allWalks);
    var fiveWeeks = document.createElement('button');
    fiveWeeks.textContent = "5 weeks";
    tag.appendChild(fiveWeeks);
    allWalks.addEventListener("click", function () {
        allWalks.classList.remove("active");
        fiveWeeks.classList.remove("active");
        var tags = document.getElementsByClassName("ml-allwalks");
        for (let i = 0; i < tags.length; i++) {
            var tag = tags[i];
            tag.style.display = "";
        }
        var tags2 = document.getElementsByClassName("ml-fivewalks");
        for (let i = 0; i < tags2.length; i++) {
            var tag = tags2[i];
            tag.style.display = "none";
        }
        allWalks.classList.add("active");

    });
  
    fiveWeeks.addEventListener("click", function () {
        allWalks.classList.remove("active");
        fiveWeeks.classList.remove("active");
        var tags = document.getElementsByClassName("ml-allwalks");
        for (let i = 0; i < tags.length; i++) {
            var tag = tags[i];
            tag.style.display = "none";
        }
        var tags2 = document.getElementsByClassName("ml-fivewalks");
        for (let i = 0; i < tags2.length; i++) {
            var tag = tags2[i];
            tag.style.display = "";
        }

        fiveWeeks.classList.add("active");
    });
    fiveWeeks.click();

}

window.addEventListener('load', function () {
    var div = "ml-buttons";
    mlSetButtons(div);
});