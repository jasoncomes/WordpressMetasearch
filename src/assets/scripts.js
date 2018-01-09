function confirmResults(message) {
  var check = confirm(message);

  if (check == true) {
    return true;
  } else {
    return false;
  }
}