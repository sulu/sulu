<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Functional\Traits;

use Symfony\Component\HttpFoundation\File\UploadedFile;

trait CreateUploadedFileTrait
{
    private function createUploadedFileVideo(): UploadedFile
    {
        $videoFile = \base64_decode('AAAAIGZ0eXBpc29tAAACAGlzb21pc28yYXZjMW1wNDEAAAAIZnJlZQAAAr9tZGF0AAACoAYF//+c3EXpvebZSLeWLNgg2SPu73gyNjQgLSBjb3JlIDEyNSAtIEguMjY0L01QRUctNCBBVkMgY29kZWMgLSBDb3B5bGVmdCAyMDAzLTIwMTIgLSBodHRwOi8vd3d3LnZpZGVvbGFuLm9yZy94MjY0Lmh0bWwgLSBvcHRpb25zOiBjYWJhYz0xIHJlZj0zIGRlYmxvY2s9MTowOjAgYW5hbHlzZT0weDM6MHgxMTMgbWU9aGV4IHN1Ym1lPTcgcHN5PTEgcHN5X3JkPTEuMDA6MC4wMCBtaXhlZF9yZWY9MSBtZV9yYW5nZT0xNiBjaHJvbWFfbWU9MSB0cmVsbGlzPTEgOHg4ZGN0PTEgY3FtPTAgZGVhZHpvbmU9MjEsMTEgZmFzdF9wc2tpcD0xIGNocm9tYV9xcF9vZmZzZXQ9LTIgdGhyZWFkcz02IGxvb2thaGVhZF90aHJlYWRzPTEgc2xpY2VkX3RocmVhZHM9MCBucj0wIGRlY2ltYXRlPTEgaW50ZXJsYWNlZD0wIGJsdXJheV9jb21wYXQ9MCBjb25zdHJhaW5lZF9pbnRyYT0wIGJmcmFtZXM9MyBiX3B5cmFtaWQ9MiBiX2FkYXB0PTEgYl9iaWFzPTAgZGlyZWN0PTEgd2VpZ2h0Yj0xIG9wZW5fZ29wPTAgd2VpZ2h0cD0yIGtleWludD0yNTAga2V5aW50X21pbj0yNCBzY2VuZWN1dD00MCBpbnRyYV9yZWZyZXNoPTAgcmNfbG9va2FoZWFkPTQwIHJjPWNyZiBtYnRyZWU9MSBjcmY9MjMuMCBxY29tcD0wLjYwIHFwbWluPTAgcXBtYXg9NjkgcXBzdGVwPTQgaXBfcmF0aW89MS40MCBhcT0xOjEuMDAAgAAAAA9liIQAV/0TAAYdeBTXzg8AAALvbW9vdgAAAGxtdmhkAAAAAAAAAAAAAAAAAAAD6AAAACoAAQAAAQAAAAAAAAAAAAAAAAEAAAAAAAAAAAAAAAAAAAABAAAAAAAAAAAAAAAAAABAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAgAAAhl0cmFrAAAAXHRraGQAAAAPAAAAAAAAAAAAAAABAAAAAAAAACoAAAAAAAAAAAAAAAAAAAAAAAEAAAAAAAAAAAAAAAAAAAABAAAAAAAAAAAAAAAAAABAAAAAAAgAAAAIAAAAAAAkZWR0cwAAABxlbHN0AAAAAAAAAAEAAAAqAAAAAAABAAAAAAGRbWRpYQAAACBtZGhkAAAAAAAAAAAAAAAAAAAwAAAAAgBVxAAAAAAALWhkbHIAAAAAAAAAAHZpZGUAAAAAAAAAAAAAAABWaWRlb0hhbmRsZXIAAAABPG1pbmYAAAAUdm1oZAAAAAEAAAAAAAAAAAAAACRkaW5mAAAAHGRyZWYAAAAAAAAAAQAAAAx1cmwgAAAAAQAAAPxzdGJsAAAAmHN0c2QAAAAAAAAAAQAAAIhhdmMxAAAAAAAAAAEAAAAAAAAAAAAAAAAAAAAAAAgACABIAAAASAAAAAAAAAABAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAGP//AAAAMmF2Y0MBZAAK/+EAGWdkAAqs2V+WXAWyAAADAAIAAAMAYB4kSywBAAZo6+PLIsAAAAAYc3R0cwAAAAAAAAABAAAAAQAAAgAAAAAcc3RzYwAAAAAAAAABAAAAAQAAAAEAAAABAAAAFHN0c3oAAAAAAAACtwAAAAEAAAAUc3RjbwAAAAAAAAABAAAAMAAAAGJ1ZHRhAAAAWm1ldGEAAAAAAAAAIWhkbHIAAAAAAAAAAG1kaXJhcHBsAAAAAAAAAAAAAAAALWlsc3QAAAAlqXRvbwAAAB1kYXRhAAAAAQAAAABMYXZmNTQuNjMuMTA0', true);

        $tempFilePath = \tempnam(\sys_get_temp_dir(), 'sulu_test_video_');

        if (!$tempFilePath) {
            throw new \RuntimeException(\sprintf(
                'Could not create temporary image in "%s".',
                __CLASS__
            ));
        }

        \file_put_contents($tempFilePath, $videoFile);

        $uploadedFile = new UploadedFile($tempFilePath, 'video.mp4');

        return $uploadedFile;
    }

    private function createUploadedFileImage(): UploadedFile
    {
        $pngImage = \base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAAA1BMVEUAAACnej3aAAAAAXRSTlMAQObYZgAAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII=', true);

        $tempFilePath = \tempnam(\sys_get_temp_dir(), 'sulu_test_image_');

        if (!$tempFilePath) {
            throw new \RuntimeException(\sprintf(
                'Could not create temporary image in "%s".',
                __CLASS__
            ));
        }

        \file_put_contents($tempFilePath, $pngImage);

        $uploadedFile = new UploadedFile($tempFilePath, '1x1.png');

        return $uploadedFile;
    }

    private function createUploadedFileSvgImage(?int $width = null, ?int $height = null): UploadedFile
    {
        $tempFilePath = \tempnam(\sys_get_temp_dir(), 'sulu_test_image_');

        if (!$tempFilePath) {
            throw new \RuntimeException(\sprintf(
                'Could not create temporary image in "%s".',
                __CLASS__
            ));
        }

        $attributes = [];
        if ($width) {
            $attributes[] = ' width="' . $width . '"';
        }
        if ($height) {
            $attributes[] = ' height="' . $height . '"';
        }

        \file_put_contents(
            $tempFilePath,
            '<svg xmlns="http://www.w3.org/2000/svg"' . \implode(' ', $attributes) . ' viewBox="0 0 600 400">
                <path xmlns="http://www.w3.org/2000/svg" fill="#eee" d="M0 0h600v400H0z"/>
            </svg>'
        );

        $uploadedFile = new UploadedFile($tempFilePath, $width . 'x' . $height . '.svg');

        return $uploadedFile;
    }
}
