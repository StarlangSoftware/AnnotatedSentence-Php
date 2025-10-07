<?php

namespace olcaytaner\AnnotatedSentence;

use olcaytaner\Corpus\Sentence;

class AnnotatedPhrase extends Sentence
{
    private int $wordIndex;

    private string $tag;

    /**
     * Constructor for AnnotatedPhrase. AnnotatedPhrase stores information about phrases such as
     * Shallow Parse phrases or named entity phrases.
     * @param int $wordIndex Starting index of the first word in the phrase w.r.t. original sentence the phrase occurs.
     * @param string $tag Tag of the phrase. Corresponds to the shallow parse or named entity tag.
     */
    public function __construct(int $wordIndex, string $tag){
        parent::__construct();
        $this->wordIndex = $wordIndex;
        $this->tag = $tag;
    }

    /**
     * Accessor for the wordIndex attribute.
     * @return int Starting index of the first word in the phrase w.r.t. original sentence the phrase occurs.
     */
    public function getWordIndex(): int
    {
        return $this->wordIndex;
    }

    /**
     * Accessor for the tag attribute.
     * @return string Tag of the phrase. Corresponds to the shallow parse or named entity tag.
     */
    public function getTag(): string
    {
        return $this->tag;
    }

}