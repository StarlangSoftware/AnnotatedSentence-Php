<?php

namespace olcaytaner\AnnotatedSentence;

use olcaytaner\Corpus\Corpus;
use olcaytaner\DependencyParser\ParserEvaluationScore;

class AnnotatedCorpus extends Corpus
{

    /**
     * A constructor of {@link AnnotatedCorpus} class which reads all {@link AnnotatedSentence} files with the file
     * name satisfying the given pattern inside the given folder. For each file inside that folder, the constructor
     * creates an AnnotatedSentence and puts in inside the list parseTrees.
     * @param string|null $folder Folder where all sentences reside.
     * @param string|null $pattern File pattern such as "." ".train" ".test".
     */
    public function __construct(?string $folder = null, ?string $pattern = null)
    {
        parent::__construct();
        if ($pattern == null) {
            foreach (glob($folder . '/*.*') as $file) {
                $sentence = new AnnotatedSentence($file);
                $this->sentences[] = $sentence;
            }
        } else {
            foreach (glob($folder . '/' . $pattern) as $file) {
                $sentence = new AnnotatedSentence($file);
                $this->sentences[] = $sentence;
            }
        }
    }

    /**
     * Compares the corpus with the given corpus and returns a parser evaluation score for this comparison. The result
     * is calculated by summing up the parser evaluation scores of sentence by sentence dependency relation comparisons.
     * @param AnnotatedCorpus $corpus Corpus to be compared.
     * @return ParserEvaluationScore A parser evaluation score object.
     */
    public function compareParses(AnnotatedCorpus $corpus): ParserEvaluationScore
    {
        $result = new ParserEvaluationScore();
        for ($i = 0; $i < $this->wordCount(); $i++) {
            $sentence1 = $this->sentences[$i];
            $sentence2 = $corpus->getSentence($i);
            $result->add($sentence1->compareParses($sentence2));
        }
        return $result;
    }
}