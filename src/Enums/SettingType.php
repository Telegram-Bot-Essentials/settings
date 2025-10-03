<?php

namespace TelegramBotEssentials\Settings\Enums;

enum SettingType: string
{
    case CHECKBOX = 'checkbox';
    case TEXT = 'text';
    case NUMBER = 'number';
    case PASSWORD = 'password';
    case ENUM = 'enum';
    case SELECT = 'select';
    case MULTISELECT = 'multiselect';
}