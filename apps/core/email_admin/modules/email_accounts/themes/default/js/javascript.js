function Activate_Option()
{
    var by_account = document.getElementById('by_account');
    var by_file    = document.getElementById('upload_file');
    if(by_account){
	if(by_account.checked==true)
	{
	    document.getElementById('save_by_account1').style.display = '';
	    document.getElementById('save_by_account2').style.display = '';
	    document.getElementById('save_by_file').style.display = 'none';
	    document.getElementById('required_field').style.display = '';
	}
	else
	{
	    document.getElementById('save_by_account1').style.display = 'none';
	    document.getElementById('save_by_account2').style.display = 'none';
	    document.getElementById('save_by_file').style.display = '';
	    document.getElementById('required_field').style.display = 'none';
	}
    }
}

$(document).ready(function(){
    $('#domain').change(function() {
        $(this).closest('form').submit();
    });
});