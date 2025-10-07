<?php

namespace olcaytaner\AnnotatedSentence;

enum ViewLayerType
{
    case PART_OF_SPEECH;
    case INFLECTIONAL_GROUP;
    case META_MORPHEME;
    case META_MORPHEME_MOVED;
    case TURKISH_WORD;
    case PERSIAN_WORD;
    case ENGLISH_WORD;
    case WORD;
    case SEMANTICS;
    case NER;
    case DEPENDENCY;
    case PROPBANK;
    case SHALLOW_PARSE;
    case ENGLISH_PROPBANK;
    case ENGLISH_SEMANTICS;
    case FRAMENET;
    case SLOT;
    case POLARITY;
    case CCG;
    case POS_TAG;
}