Amazon Web Services Tools for TYPO3
===================================
[![Latest Stable Version](https://poser.pugx.org/leuchtfeuer/aws-tools/v/stable)](https://packagist.org/packages/leuchtfeuer/aws-tools)
[![Total Downloads](https://poser.pugx.org/leuchtfeuer/aws-tools/downloads)](https://packagist.org/packages/leuchtfeuer/aws-tools)
[![Latest Unstable Version](https://poser.pugx.org/leuchtfeuer/aws-tools/v/unstable)](https://packagist.org/packages/leuchtfeuer/aws-tools)
[![Code Climate](https://codeclimate.com/github/Leuchtfeuer/typo3-aws-tools/badges/gpa.svg)](https://codeclimate.com/github/Leuchtfeuer/typo3-aws-tools)
[![License](https://poser.pugx.org/leuchtfeuer/aws-tools/license)](https://packagist.org/packages/leuchtfeuer/aws-tools)

This extension connects your TYPO3 instance to Amazon CloudFront. It rewrites all file paths in the frontend to match your CDN
domain. You also have the possibility (at several places) to invalidate Amazon CloudFront entries.
The complete documentation can be found [here](https://docs.typo3.org/p/leuchtfeuer/aws-tools/master/en-us/).

## Installation

There are several ways to require and install this extension. We recommend getting this extension via composer.

### Via Composer

If your TYPO3 instance is running in composer mode, you can simply require the extension by running:

    composer req leuchtfeuer/aws-tools

### Via Extension Manager

Open the extension manager module of your TYPO3 instance and select “Get Extensions” in the select menu above the upload button. 
There you can search for "aws_tools" and simply install the extension. Please make sure you are using the latest version of the 
extension by updating the extension list before installing the AWS Tools extension.

### Via ZIP Archive

You need to download the AWS Tools extension from the 
[TYPO3 Extension Repository (TER)](https://extensions.typo3.org/extension/aws_tools/ "aws_tools in TER")  and upload the zip file 
to the extension manager of your TYPO3 instance and activate the extension afterwards.

## Configuration

To activate the Content Delivery Network and to be able to invalidate Amazon CloudFront entries, several settings are necessary,
which have to be carried out in different places (TypoScript, Site Configuration, Extension Configuration, ...).

### Site Configuration

The Content Delivery Network  (CDN) can be enabled and configured (regardless if Amazon CloudFront is used) in the language
configuration of the page configuration. The CDN can be enabled or disabled per domain and per language.

![CDN Settings within the Site Configuration](https://raw.githubusercontent.com/Leuchtfeuer/typo3-aws-tools/master/Documentation/Images/site-configuration.png "CDN Settings within the Site Configuration")

### Rewrite File Paths

All paths to assets located in resource stores (fileadmin, etc.) are automatically rewritten by a TYPO3 interface. Since not all
files are "fetched" by this mechanism, file paths (e.g. paths to stylesheets or JavaScript and fonts located in a separate site 
extension) configured in the TypoScript setup are rewritten by a regular expression.

```
config.tx_awstools {
    patterns {
        10 {
            search = "/typo3temp/
            replace = "%s/typo3temp/
        }

        20 {
            search = "/typo3conf/
            replace = "%s/typo3conf/
        }
    }
}
```

The "patterns" option can be extended by any number of additional entries. Each property must have the keys "search" and 
"replace". The `%s` in the replace property will be replaced by the previously configured CDN domain.

Please note the quotation mark in the values of "search" and "replace". The HTML source code of the page is searched for this 
pattern, so that it applies to `href="/typo3temp/assets/...` for example. If the pattern did not include the quotation mark, this 
would lead to serious errors, as links like `href="https://cdn.example.com/typo3temp/assets/"` would be rewritten to 
`https://cdn.example.comhttps://cdn.example.com/typo3temp/assets/`.

### Extension Configuration

In the extension configuration you enter the access data for your AWS account (Access Key ID and Secret Access Key). In addition, 
you must select the region over which the requests to the AWS servers to invalidate entries should run. On the CloudFront tab, 
you can specify a comma-separated list of distributions where your assets are stored.

![AWS Tools extension configuration](https://raw.githubusercontent.com/Leuchtfeuer/typo3-aws-tools/master/Documentation/Images/extension-configuration.png "AWS Tools extension configuration")

## Invalidate Amazon CloudFront Entries

Amazon CloudFront entries are partially invalidated automatically or can be manually declared invalid by the user or command line 
calls.

### Symfony Command (CLI Only)

Amazon CloudFront entries can be invalidated via a CLI call. The command expects one or more paths (to files or folders).

    vendor/bin/typo3cms aws:cf:invalidate PATH_1 PATH_2 [...] PATH_X

The command can be executed after a deployment, for example. This command is not available as scheduler task.

### File List Module

Amazon CloudFront entries can be invalidated using the File List module. Depending on user permissions (see: below) files or whole 
paths can be invalidated.

![Invalidate entries in the File List module](https://raw.githubusercontent.com/Leuchtfeuer/typo3-aws-tools/master/Documentation/Images/file-list.png "Invalidate entries in the File List module")

#### Access Protection

For a user to be able to invalidate Amazon CloudFront entries, she or he must have the appropriate permissions. The permissions 
can be granted in the backend group or user data record (`invalidateFile` or `invalidateFolder`).

![Configure permissions for users or groups](https://raw.githubusercontent.com/Leuchtfeuer/typo3-aws-tools/master/Documentation/Images/user-permissions.png "Configure permissions for users or groups")

Administrators can invalidate the entries without further permissions.

#### Automatic Invalidation on Overwriting

Amazon CloudFront entries will automatic being invalidated after overwriting an existing file in the file list module - regardless 
of the authorizations of the user.

### Backend Module

A dedicated backend module (only accessible for administrators) offers another possibility to invalidate Amazon CloudFront 
entries. The module also lists the last ten requests (per distribution) to the AWS server.

![Backend view of the AWS Tools module](https://raw.githubusercontent.com/Leuchtfeuer/typo3-aws-tools/master/Documentation/Images/backend-module.png "Backend view of the AWS Tools module")

![Backend view of the AWS Tools module after invalidating entries](https://raw.githubusercontent.com/Leuchtfeuer/typo3-aws-tools/master/Documentation/Images/backend-module-invalidation.png "Backend view of the AWS Tools module after invalidating entries")
