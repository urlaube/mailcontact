// react on mailcontact anchors
if (window.location.hash) {
  var hash = window.location.hash.substring(1);

  // only react on certain anchors
  if (("mailcontact-failure" == hash) ||
      ("mailcontact-success" == hash)) {
    // retrieve element
    var element = document.getElementById(hash+"-alert");
    if (null != element) {
      // show element
      element.style.display = "block";
    }
  }
}
