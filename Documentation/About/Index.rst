.. include:: ../Includes.txt

.. _about:

=====
About
=====

This extension connects your TYPO3 instance to `Amazon CloudFront <https://aws.amazon.com/cloudfront/>`__. It rewrites all file
paths in the frontend to match your CDN domain. You also have the possibility (at several places) to invalidate Amazon CloudFront
entries.

.. _about-compatibility:

Compatibility
=============

You need access to an Auth0 instance. We are currently supporting following TYPO3 versions:

.. csv-table:: Version Matrix
   :header: "Extension Version", "TYPO3 v10 Support", "TYPO3 v9 Support", "TYPO3 v8 Support"
   :align: center

        "0.x", "🙋‍♂️", "🙅‍♀️", "🙅‍♀️"

.. _about-aboutAmazonCloudFront:

About Amazon CloudFront
=======================

Amazon CloudFront is a fast content delivery network (CDN) service that securely delivers data, videos, applications, and APIs to
customers globally with low latency, high transfer speeds, all within a developer-friendly environment. CloudFront is integrated
with AWS – both physical locations that are directly connected to the AWS global infrastructure, as well as other AWS services.
CloudFront works seamlessly with services including AWS Shield for DDoS mitigation, Amazon S3, Elastic Load Balancing or Amazon
EC2 as origins for your applications, and Lambda@Edge to run custom code closer to customers’ users and to customize the user
experience. Lastly, if you use AWS origins such as Amazon S3, Amazon EC2 or Elastic Load Balancing, you don’t pay for any data
transferred between these services and CloudFront.

.. toctree::
   :hidden:

   Contributing/Index
   Changelog/Index
