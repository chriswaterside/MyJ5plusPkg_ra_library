<?php
namespace RamblerWebs\RamblersLibrary\jsonwalks;

enum SourceOfWalk: string {

    case Unknown = '?';
    case GWEM = 'gwem';
    case WManager = 'wm';
    case WEditor = 'we';
}