function defaultValues(totalRepos)
{
    for(var i=0;i<totalRepos;i++){
        var chkbox = document.getElementById("repo-"+i);
	var repo = $("#repo-"+i).parent().next().html();
	if(repo == "CentOS-5 - Base" || repo == "CentOS-5 - Updates" || repo == "CentOS-5 - Addons" || repo == "CentOS-5 - Extras" || repo == "Base RPM Repository for Elastix" || repo == "Updates RPM Repository for Elastix" || repo == "Extras RPM Repository for Elastix" || repo == "Extra Packages for Enterprise Linux 5 - $basearch" || repo == "Base RPM Repository for Elastix Commercial-Addons" || repo == "Loway Research Yum Repository")
            chkbox.checked = true;
        else
            chkbox.checked = false;
    }
}