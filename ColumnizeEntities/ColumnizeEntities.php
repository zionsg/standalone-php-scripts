<?php
/**
 * Output entities in columns
 *
 * Usage:
 *     $instance = new ColumnizeEntities();
 *     $params = array();
 *     echo $instance($params);
 *
 * Result can be formatted with CSS by passing params to instance - @see __invoke()
 *
 * @author  Zion Ng <zion@intzone.com>
 * @link    [Source] https://github.com/zionsg/standalone-php-scripts/tree/master/ColumnizeEntities
 * @since   2012-11-06T23:30+08:00
 */
class ColumnizeEntities
{
    /**
     * Method invoked when script calls instance as a function
     *
     * Output entities in columns
     *
     * For each entity, its thumbnail image and name are shown and both are
     * hyperlinked to a specified url. If another format is desired,
     * $entityCallback can be used
     *
     * In the scenario where the no. of entities is not divisible by the no.
     * of columns, the remaining entities are centered in the last row
     *
     * @param  array $params Key-value pairs. All paths should NOT have trailing slashes
     *         @key int      $cols           DEFAULT=1. No. of columns to split entities in
     *         @key object[] $entities       Array of entity objects
     *         @key callback $entityCallback Callback function that takes in entity and returns formatted
     *                                       HTML for entity. If this is not defined, the default format
     *                                       of url, thumbnail and name is used
     *         @key string   $nameClass      CSS class for entity name
     *         @key callback $nameCallback   Callback function that takes in entity and returns name
     *         @key boolean  $leftToRight    DEFAULT=true. Whether to list entities from left to right
     *                                       or top to down. Examples with $remainderAlign set to 'center'
     *                                           Left to right
     *                                           1   2   3
     *                                           4   5   6
     *                                             7   8
     *
     *                                           Top to down
     *                                           1   3   5
     *                                           2   4   6
     *                                             7   8
     *         @key string   $remainderAlign DEFAULT='center'. How to align the remainder entities in
     *                                       the last row. Possible values: left, center.
     *         @key string   $tableClass     CSS class for entire table
     *         @key string   $tableId        'id' attribute for entire table, to facilitate DOM reference
     *         @key string   $tdClass        CSS class for <td> enclosing entity
     *         @key string   $trClass        CSS class for <tr> enclosing entity <td>
     *         @key callback $urlCallback    Callback function that takes in entity and returns entity url
     *         @key string   $urlClass       CSS class for entity url
     *         @key string   $urlTarget      Target for entity url. <a target="<$urlTarget>"...
     *
     *         Keys for drawing thumbnail images:
     *         @key boolean  $drawThumbnailBox   DEFAULT=true. Whether to enclose thumbnail <img> in <td>.
     *                                           If true, box will be drawn even if there's no thumbnail
     *         @key string   $thumbnailBoxClass  CSS class for <td> box enclosing thumbnail image
     *         @key string   $thumbnailClass     CSS class for thumbnail image
     *         @key callback $thumbnailCallback  Callback function that takes in entity and returns
     *                                           thumbnail filename
     *         @key string   $thumbnailPath      Folder path relative to web root where thumbnail is stored
     *         @key int      $maxThumbnailHeight Maximum height constraint for thumbnail image
     *                                           If set to 0, "height" attribute will be skipped in output
     *         @key int      $maxThumbnailWidth  Maximum width constraint for thumbnail image
     *                                           If set to 0, "width" attribute will be skipped in output
     *         @key string   $webRoot            Absolute path for web root. Used for retrieving thumbnail
     * @return string
     * @throws InvalidArgumentException When any of the callbacks is not callable
     */
    public function __invoke(array $params)
    {
        // Make sure all keys are set before extracting to prevent notices
        $params = array_merge(
            array(
                'cols' => 1,
                'entities' => array(),
                'entityCallback' => null,
                'nameClass' => '',
                'nameCallback' => null,
                'leftToRight' => true,
                'remainderAlign' => 'center',
                'tableClass' => '',
                'tableId' => '',
                'tdClass' => '',
                'trClass' => '',
                'urlCallback' => null,
                'urlClass' => '',
                'urlTarget' => '',
                // keys for drawing thumbnails
                'drawThumbnailBox' => true,
                'thumbnailBoxClass' => '',
                'thumbnailCallback' => null,
                'thumbnailClass' => '',
                'thumbnailPath' => '',
                'maxThumbnailHeight' => 0,
                'maxThumbnailWidth' => 0,
                'webRoot' => '',
            ),
            $params
        );
        extract($params);

        // Convert associative array to numerically indexed array
        $entities = array_values($entities);
        $entityCount = count($entities);
        if ($entityCount == 0) return '';

        // Calculate initial rows
        if (!in_array($remainderAlign, array('left', 'center'))) {
            $remainderAlign = 'center';
        }
        if ($remainderAlign == 'left') {
            $initialRows = (int) ceil($entityCount / $cols);
        } elseif ($remainderAlign == 'center') {
            $cols = min($cols, $entityCount);
            $initialRows = (int) floor($entityCount / $cols);
        }
        $tdWidth = 100 / $cols;
        $entitiesProcessed = 0;

        // Process entities and generate output
        $output = sprintf('<table id="%s" class="%s" width="100%%">' . PHP_EOL, $tableId, $tableClass);
        for ($row = 0; $row < $initialRows; $row++) {
            $output .= sprintf('<tr class="%s">' . PHP_EOL, $trClass);
            for ($col = 0; $col < $cols; $col++) {
                $output .= sprintf('<td class="%s" width="%d%%">' . PHP_EOL, $tdClass, $tdWidth);

                // Get entity, depending on listing order (left-right or top-down)
                if ($leftToRight) {
                    $index = ($row * $cols) + $col;
                } else {
                    $index = ($col * $initialRows) + $row;
                }
                if ($index >= $entityCount) {
                    $output .= '</td>' . PHP_EOL; // remember to close td
                    continue;
                }
                $entity = $entities[$index];

                // Get entity output
                $entityOutput = '';

                if ($entityCallback) {
                    if (!is_callable($entityCallback)) {
                        throw new InvalidArgumentException('Invalid entity callback provided');
                    }
                    $entityOutput = $entityCallback($entity) . PHP_EOL;
                } else {
                    // Get entity thumbnail
                    $thumbnail = null;
                    if ($thumbnailCallback) {
                        if (!is_callable($thumbnailCallback)) {
                            throw new InvalidArgumentException('Invalid thumbnail callback provided');
                        }
                        $thumbnail = $thumbnailCallback($entity);
                    }

                    // Draw thumbnail
                    $thumbnailOutput = '';
                    if ($thumbnail !== null) {
                        $imagePath = $webRoot . $thumbnailPath . '/' . $thumbnail;
                        if (!file_exists($imagePath)) {
                            $thumbnailOutput .= PHP_EOL;
                        } else {
                            list($width, $height, $type, $attr) = getimagesize($imagePath);

                            if ($maxThumbnailWidth != 0 && $width > $maxThumbnailWidth) {
                                $height = ($height / $width) * $maxThumbnailWidth;
                                $width  = $maxThumbnailWidth;
                            }

                            if ($maxThumbnailHeight != 0 && $height > $maxThumbnailHeight) {
                                $width  = ($width / $height) * $maxThumbnailHeight;
                                $height = $maxThumbnailHeight;
                            }

                            $thumbnailOutput = sprintf(
                                '<img class="%s" align="center" src="%s" %s %s />' . PHP_EOL,
                                $thumbnailClass,
                                $thumbnailPath . '/' . $thumbnail,
                                ($maxThumbnailWidth == 0 ? '' : "width=\"{$width}\""),
                                ($maxThumbnailHeight == 0 ? '' : "height=\"{$height}\"")
                            );
                        } // end if thumbnail file exists

                        if ($drawThumbnailBox) {
                            $thumbnailOutput = sprintf(
                                '<table align="center" cellspacing="0" cellpadding="0">' . PHP_EOL
                                . '<tr><td class="%s" %s %s align="center" valign="middle">' . PHP_EOL
                                . '%s'
                                . '</td></tr>' . PHP_EOL
                                . '</table>' . PHP_EOL,
                                $thumbnailBoxClass,
                                ($maxThumbnailWidth == 0 ? '' : "width=\"{$maxThumbnailWidth}\""),
                                ($maxThumbnailHeight == 0 ? '' : "height=\"{$maxThumbnailHeight}\""),
                                $thumbnailOutput
                            );
                        }
                    } // end draw thumbnail

                    // Get entity url
                    $url = null;
                    if ($urlCallback) {
                        if (!is_callable($urlCallback)) {
                            throw new InvalidArgumentException('Invalid url callback provided');
                        }
                        $url = $urlCallback($entity);
                        $entityOutput .= sprintf(
                            '<a class="%s" target="%s" href="%s">' . PHP_EOL,
                            $urlClass, $urlTarget, $url
                        );
                    }

                    // Insert thumbnail output
                    $output .= $thumbnailOutput;

                    // Get entity name
                    $name = null;
                    if ($nameCallback) {
                        if (!is_callable($nameCallback)) {
                            throw new InvalidArgumentException('Invalid name callback provided');
                        }
                        $name = $nameCallback($entity);
                        $entityOutput .= sprintf('<div class="%s">%s</div>' . PHP_EOL, $nameClass, $name);
                    }

                    // Close </a> if there is an entity url
                    if ($url !== null) {
                        $entityOutput .= '</a>' . PHP_EOL;
                    }
                } // end entity output

                $output .= $entityOutput . '</td>' . PHP_EOL;
                $entitiesProcessed++;
            } // end for cols
            $output .= '</tr>' . PHP_EOL;
        } // end for rows
        $output .= '</table>' . PHP_EOL;

        // Call function again to output remaining entities
        $remainderCount = $entityCount % $cols;
        if ($remainderCount == 0) {
            return $output;
        } else {
            $remainderEntities = array();
            for ($i = $entitiesProcessed; $i < $entityCount; $i++) {
                $remainderEntities[] = $entities[$i];
            }
            $params['cols'] = $remainderCount;
            $params['entities'] = $remainderEntities;
            return $output . $this->__invoke($params);
        }

    } // end function __invoke

} // end class