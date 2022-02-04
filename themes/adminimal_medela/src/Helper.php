<?php

namespace Drupal\adminimal_medela;

use Drupal\media\Entity\Media;
use Drupal\Core\File\FileSystemInterface;
use Drupal\field\Entity\FieldConfig;

/**
 * Provides helper functions.
 */
class Helper {

  /**
   * Unpublish media from old node revisions.
   */
  public static function unpublishMediaFromOldNodeRevisions($nid, $field_name) {
    $node_storage = \Drupal::service('entity_type.manager')->getStorage('node');
    $media_storage = \Drupal::service('entity_type.manager')->getStorage('media');

    // Load the current node revision.
    if ($node = $node_storage->load($nid)) {

      // Load all revisions of the node.
      $vids = $node_storage->revisionIds($node);
      $revisions = $node_storage->loadMultipleRevisions($vids);

      // Initialise arrays.
      $media_ids_keep = [];
      $media_ids_remove = [];

      // Go through each revision and find attached media.
      foreach ($revisions as $revision) {
        if ($revision->{$field_name}) {
          $medias = $revision->{$field_name}->getValue();
          foreach ($medias as $media) {
            // If node is unpublished, archive all media.
            if (!$node->isPublished()) {
              $media_ids_remove[] = $media['target_id'];
            }
            elseif ($node->isPublished() && $revision->vid->value != $node->vid->value) {
              // Node::load will return the default node, which is the latest
              // published revision or latest draft if no published exists.
              // We want to archive any media that is not on the published
              // version.
              $media_ids_remove[] = $media['target_id'];
            }
            else {
              // Node is published and this is the published revision,
              // keep it.
              $media_ids_keep[] = $media['target_id'];
            }
          }
        }
      }
      // Archive media no longer being used.
      $media_ids_archive = array_diff(array_unique($media_ids_remove), array_unique($media_ids_keep));
      $medias_archive = $media_storage->loadMultiple($media_ids_archive);
      foreach ($medias_archive as $media) {
        Helper::archiveMedia($media);
      }

      // Unarchive media that is currently being used.
      // For example when a draft becomes published, or a page is unarchived.
      $medias_unarchive = $media_storage->loadMultiple($media_ids_keep);
      foreach ($medias_unarchive as $media) {
        Helper::unarchiveMedia($media);
      }
    }
  }

  /**
   * Archive a managed media entity.
   *
   * Move the file from the public to the private system and unpublish the
   * media entity.
   *
   * @param Drupal\media\Entity\Media $media
   *   Media entity.
   *
   * @return bool
   *   TRUE if successful, FALSE if not.
   */
  public static function archiveMedia(Media $media) {
    $file_storage = \Drupal::service('entity_type.manager')->getStorage('file');
    $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager');

    // Make sure this media type is a 'Managed' one.
    if (in_array($media->bundle(), ['h_managed_files', 'h_managed_images'])) {
      // If the media entity is published, we should unpublish it
      // and move the file.
      if ($media->isPublished()) {
        $definitions = $media->getFieldDefinitions();
        foreach ($definitions as $definition) {
          if ($definition instanceof FieldConfig) {
            if ($definition->getSetting('handler') == 'default:file') {
              $fid = $media->{$definition->get('field_name')}->target_id;
              $file = $file_storage->load($fid);
              // Move the file into the private system.
              $destination = 'private://' . $stream_wrapper_manager->getTarget($file->getFileUri());
              file_move($file, $destination, FileSystemInterface::EXISTS_RENAME);
            }
          }
        }

        // Unpublish so we know it is not in use anymore.
        return $media->setUnpublished()->save();
      }
    }
    return FALSE;
  }

  /**
   * Unarchive a managed media entity.
   *
   * Move the file back to the public system and publish the media entity.
   *
   * @param Drupal\media\Entity\Media $media
   *   Media entity.
   *
   * @return bool
   *   TRUE if successful, FALSE if not.
   */
  public static function unarchiveMedia(Media $media) {
    $file_storage = \Drupal::service('entity_type.manager')->getStorage('file');
    $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager');

    // Make sure this media type is a 'Managed' one.
    if (in_array($media->bundle(), ['h_managed_files', 'h_managed_images'])) {
      // If the media entity is published, we should unpublish it
      // and move the file.
      if (!$media->isPublished()) {
        $definitions = $media->getFieldDefinitions();
        foreach ($definitions as $definition) {
          if ($definition instanceof FieldConfig) {
            if ($definition->getSetting('handler') == 'default:file') {
              $fid = $media->{$definition->get('field_name')}->target_id;
              $file = $file_storage->load($fid);
              // Move the file into the private system.
              $destination = 'public://' . $stream_wrapper_manager->getTarget($file->getFileUri());
              file_move($file, $destination, FileSystemInterface::EXISTS_RENAME);
            }
          }
        }

        // Unpublish so we know it is not in use anymore.
        return $media->setPublished()->save();
      }
    }
    return FALSE;
  }

}
