<?php

namespace App\Listings;

/**
 * Input kind of a wizard field. The Blade templates still render these; the value is the contract.
 */
enum FieldType: string
{
    case TranslatableText = 'translatable_text';
    case TranslatableTextarea = 'translatable_textarea';
    case Text = 'text';
    case Number = 'number';
    case Integer = 'integer';
    case Select = 'select';
    case Multiselect = 'multiselect';
    case Boolean = 'boolean';
    case Date = 'date';
    case Phone = 'phone';
    case Email = 'email';
    case Photos = 'photos';
    case Schema = 'schema';
}
