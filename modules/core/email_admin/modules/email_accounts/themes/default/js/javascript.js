$(document).ready(function() {
    var domain = $('#domain').val();
    $('a[href="?menu=email_accounts&exportcsv=yes&rawmode=yes"]').attr('href','?menu=email_accounts&exportcsv=yes&rawmode=yes&domain='+domain);
    $('a[href="?menu=email_accounts&exportspreadsheet=yes&rawmode=yes"]').attr('href','?menu=email_accounts&exportspreadsheet=yes&rawmode=yes&domain='+domain);
    $('a[href="?menu=email_accounts&exportpdf=yes&rawmode=yes"]').attr('href','?menu=email_accounts&exportpdf=yes&rawmode=yes&domain='+domain);
});

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
	}
	else
	{
	    document.getElementById('save_by_account1').style.display = 'none';
	    document.getElementById('save_by_account2').style.display = 'none';
	    document.getElementById('save_by_file').style.display = '';
	}
    }
}