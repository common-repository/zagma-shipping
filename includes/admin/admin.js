var e = document.getElementById('zash_zagma[connection]');
var strUser = e.value;
displayConZa(strUser);
e.addEventListener('change', function() {
	displayConZa(this.value);
});
function displayConZa(id){
	if(id == 1){
		document.getElementById( 'zash_zagma[token]' ).parentElement.parentElement.style.display = '';
		document.getElementById( 'zash_zagma[username]' ).parentElement.parentElement.style.display = 'none';
		document.getElementById( 'zash_zagma[password]' ).parentElement.parentElement.style.display = 'none';
		document.getElementById( 'zash_zagma[shop_id]' ).parentElement.parentElement.style.display = 'none';
	}else{
		document.getElementById( 'zash_zagma[token]' ).parentElement.parentElement.style.display = 'none';
		document.getElementById( 'zash_zagma[username]' ).parentElement.parentElement.style.display = '';
		document.getElementById( 'zash_zagma[password]' ).parentElement.parentElement.style.display = '';
		document.getElementById( 'zash_zagma[shop_id]' ).parentElement.parentElement.style.display = '';
	}
	
}