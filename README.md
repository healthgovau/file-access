# File access custom module for GovCMS

Department of Health and Department of Agriculture are collaborating on a solution to several file access issues that we are both experiencing.

## Problems

 - Files in the public file system are accessible immediately and stay on the server forever
   - Users can access outdated, sensitive or redacted files that they should no longer be able to access
   - Users can access files immediately when the file may not be ready to be released to the public
 - Authors cannot delete files to fix this themselves

## Drupal file issues

 - [Dealing with unexpected file deletion due to incorrect file usage](https://www.drupal.org/project/drupal/issues/2821423)
 - [Track media usage](https://www.drupal.org/project/drupal/issues/2835840)
 - [Make private file access handling respect the full entity reference chain](https://www.drupal.org/project/drupal/issues/2904842)
 - [Unpublished content and attachments should be kept private](https://www.drupal.org/project/drupal/issues/1836080)
 - [Permissions by Entity affected by cache](https://www.drupal.org/project/permissions_by_term/issues/3222563), potentionally redundant with Permissions by Term removed from GovCMS 9

## User stories

 - As a website user, I want to get the latest information, so that I can make informed decisions
 - As an author, I don't want the public to access outdated information, so that they do not get incorrect or redacted information
 - As an author, I don't want the public to access archived information, so that they do not get incorrect information
 - As an author, I don't want the public to access content I am drafting, so that they do not get incorrect information or see media before it is ready
 - As an author, I want to know that a media item is not being used before deleting it, so that I don't delete something in use
 - As an author, I want to draft and publish content and media together at the same time, so that my authoring time is efficient
 - As a site admin, I want to know what media has been deleted by who and when, so that we have a complete audit trail

## Requirements

 - Anonymous users should not be able to access files (media entities) that are not connected to a published revision of a node entity
 - Logged in uses should always be able to access files

## Solutions

 - Move files between the public and private system depending on whether they should be accessible to the public
 - When entities are created, updated or deleted, check related media and make sure they are in the correct file system
 - Should support revisions and moderation on nodes and media entities, including reverting revisions and support for paragraphs
