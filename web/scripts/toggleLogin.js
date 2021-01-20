var inscription = document.getElementById("inscription");
var connexion = document.getElementById("connexion");

var btn_inscription = document.getElementById("btn_inscription");
var btn_connexion = document.getElementById("btn_connexion");

if(inscription_form == 0)
{
  inscription.style.display = 'none';
}
else {
  connexion.style.display = 'none';
}

btn_inscription.addEventListener("click", function()
{
  connexion.style.display = 'none';
  inscription.style.display = 'block';
});
btn_connexion.addEventListener("click", function()
{
  inscription.style.display = 'none';
  connexion.style.display = 'block';
});
