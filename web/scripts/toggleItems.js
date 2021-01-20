function toggleItems(evt)
{
  let elmt = evt.target.parentNode.parentNode.parentNode.querySelector('.desc_item');

  if(elmt.style.display == "none")
  {
    elmt.style.display = "";
    evt.target.innerHTML = "Voir moins";
  }
  else
  {
    elmt.style.display = "none";
    evt.target.innerHTML = "Voir plus";
  }
}

var btn_voir_plus = document.getElementsByClassName("btn_voir_plus");

for(var i = 0; i < btn_voir_plus.length; i++)
{
  btn_voir_plus[i].addEventListener("click", toggleItems);
}
