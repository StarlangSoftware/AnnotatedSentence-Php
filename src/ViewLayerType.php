<?php

namespace olcaytaner\AnnotatedSentence;

enum ViewLayerType: int
{
    case PART_OF_SPEECH = 0;
    case INFLECTIONAL_GROUP = 1;
    case META_MORPHEME = 2;
    case META_MORPHEME_MOVED = 3;
    case TURKISH_WORD = 4;
    case PERSIAN_WORD = 5;
    case ENGLISH_WORD = 6;
    case WORD = 7;
    case SEMANTICS = 8;
    case NER = 9;
    case DEPENDENCY = 10;
    case PROPBANK = 11;
    case SHALLOW_PARSE = 12;
    case ENGLISH_PROPBANK = 13;
    case ENGLISH_SEMANTICS = 14;

    case FRAMENET = 15;
    case SLOT = 16;
    case POLARITY = 17;
    case CCG = 18;
    case POS_TAG = 19;
}