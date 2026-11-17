<?php

namespace App\Livewire\Admin\Items;

/** Fixed option lists for the Items screens (Russian admin labels). */
class Catalog
{
    /** Drawn illustration kinds understood by <x-site.scale-ill>. */
    public const KINDS = [
        'hang' => 'Подвесные (hang)',
        'pole' => 'Со стойкой (pole)',
        'flat' => 'Плоские (flat)',
        'c2ai' => 'C2-AI (c2ai)',
        'aipc' => 'ИИ-весы на базе ПК (aipc)',
        'y2l' => 'Y2L (y2l)',
        'comm' => 'С передачей данных (comm)',
        'c3' => 'C3 (c3)',
    ];

    /** illustration.head */
    public const HEADS = [
        '' => 'Обычная',
        'box' => 'Корпус-блок (box)',
        'wide' => 'Широкая (wide)',
    ];

    public const LABEL_PRINT = [
        'yes' => 'Печатает этикетки',
        'receipt' => 'Только чеки',
        'no' => 'Без печати',
    ];

    /** conn keys */
    public const CONN = [
        'eth' => 'Ethernet',
        'rs232' => 'RS232',
        'usb' => 'USB',
        'wifi' => 'Wi-Fi',
        'bt' => 'Bluetooth',
    ];

    /** conn values ('' = not available, the key is not stored) */
    public const CONN_STATES = [
        '' => 'Нет',
        'std' => 'В стандарте',
        'opt' => 'Опция',
    ];

    /** Feature pictograms (<x-site.icon>) used by the seeded models. */
    public const FEATURE_ICONS = [
        'hook', 'label', 'printer', 'drop', 'display', 'usb', 'keys', 'net',
        'flat', 'camera', 'fold', 'pos', 'touch', 'os', 'drawer', 'shield',
    ];

    public const DOCUMENT_TYPES = [
        'manual' => 'Руководство',
        'software' => 'Драйвер / ПО для ПК',
        'sdk' => 'SDK и протоколы',
    ];

    /** Spec keys per group, in display order (labels are ui('spec.'.$key), groups ui('grp.'.$group)). */
    public const SPEC_KEYS = [
        'weighing' => ['maxCap', 'minCap', 'interval', 'accClass', 'platter', 'netWeight'],
        'display' => ['display', 'custDisplay', 'keys', 'notif', 'camera'],
        'system' => ['cpu', 'ram', 'storage', 'os', 'memory'],
        'printer' => ['printType', 'printWidth', 'printSpeed', 'printRes', 'paperOut', 'paperStore', 'barcodes'],
        'conn' => ['ethernet', 'serial', 'usb', 'hostLink', 'cashDrawer', 'wifi', 'bluetooth'],
        'power' => ['powerSrc', 'input'],
        'physical' => ['dims', 'protection', 'antiCheat', 'opTemp', 'opHum', 'stTemp', 'stHum'],
        'compliance' => ['certs', 'standards', 'uzMetrology'],
        'box' => ['inBox'],
    ];

    /** Uploads: images for models, files for documents. Sizes in kilobytes. */
    public const IMAGE_MIMES = 'jpg,jpeg,png,webp';

    public const IMAGE_MAX_KB = 5120;

    public const GALLERY_MAX = 12;

    public const FILE_MIMES = 'pdf,zip,rar,7z,doc,docx,xls,xlsx,txt';

    public const FILE_MAX_KB = 12288;

    /** Content types (as detected from the file content) accepted for each document extension. */
    public const FILE_CONTENT_TYPES = [
        'pdf' => ['application/pdf'],
        'zip' => ['application/zip', 'application/x-zip-compressed'],
        'rar' => ['application/x-rar', 'application/vnd.rar', 'application/x-rar-compressed'],
        '7z' => ['application/x-7z-compressed'],
        'doc' => ['application/msword', 'application/CDFV2', 'application/vnd.ms-office', 'application/x-ole-storage'],
        'xls' => ['application/vnd.ms-excel', 'application/CDFV2', 'application/vnd.ms-office', 'application/x-ole-storage'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream'],
        'txt' => ['text/plain'],
    ];

    /** Russian label of a ui string, or the fallback when the string is not seeded. */
    public static function uiLabel(string $key, string $fallback): string
    {
        $text = ui($key, [], 'ru');

        return $text === '' || $text === '['.$key.']' ? $fallback : $text;
    }
}
