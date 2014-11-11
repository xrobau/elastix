UPDATE configuration_fax_mail
SET content = 'Fax sent from "{COMPANY_NAME_FROM}". The phone number is {COMPANY_NUMBER_FROM}.
This email has a fax attached with ID {NAME_PDF}.
Final status of fax job: {JOB_STATUS}'
WHERE content = 'Fax sent from "{COMPANY_NAME_FROM}". The phone number is {COMPANY_NUMBER_FROM}.
This email has a fax attached with ID {NAME_PDF}.
Estado final del trabajo de fax: {JOB_STATUS}';
