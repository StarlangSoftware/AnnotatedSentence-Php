<?php

namespace olcaytaner\AnnotatedSentence;

use olcaytaner\Corpus\Sentence;
use olcaytaner\DependencyParser\ParserEvaluationScore;
use olcaytaner\DependencyParser\Universal\UniversalDependencyRelation;
use olcaytaner\Framenet\FrameNet;
use olcaytaner\MorphologicalAnalysis\MorphologicalAnalysis\FsmMorphologicalAnalyzer;
use olcaytaner\Propbank\FramesetList;
use olcaytaner\WordNet\WordNet;

class AnnotatedSentence extends Sentence
{
    private string $file;

    /**
     * Reads an annotated sentence from a text file.
     * Converts a simple sentence to an annotated sentence.
     * @param string|null $param File containing the annotated sentence. OR Simple sentence.
     */
    public function __construct(?string $param = null)
    {
        parent::__construct();
        $this->words = [];
        if ($param !== null) {
            if (str_contains($param, '.txt') || str_contains($param, '.dev') ||
                str_contains($param, '.train') || str_contains($param, '.test')) {
                $this->file = $param;
                $fh = fopen($param, 'r');
                $line = trim(fgets($fh));
                $wordList = explode(" ", $line);
                foreach ($wordList as $word) {
                    $this->words[] = new AnnotatedWord($word);
                }
                fclose($fh);
            } else {
                $wordList = explode(" ", $param);
                foreach ($wordList as $word) {
                    $this->words[] = new AnnotatedWord($word);
                }
            }
        }
    }

    /**
     * Returns file name of the sentence
     * @return string File name of the sentence
     */
    public function getFileName(): string
    {
        return $this->file;
    }

    public function getDependencyGroups(int $rootWordIndex): array{
        $groups = [];
        for ($i = 0; $i < count($this->words); $i++) {
            $tmpWord = $this->words[$i];
            $index = $i + 1;
            while ($tmpWord->getUniversalDependency()->to() != $rootWordIndex && $tmpWord->getUniversalDependency()->to() != 0) {
                $index = $tmpWord->getUniversalDependency()->to();
                $tmpWord = $this->words[$tmpWord->getUniversalDependency()->to() - 1];
            }
            if ($tmpWord->getUniversalDependency()->to() != 0) {
                if (array_key_exists($index, $groups)) {
                    $phrase = $groups[$index];
                } else {
                    $phrase = new AnnotatedPhrase($i, $tmpWord->getUniversalDependency()->__toString());
                    $groups[$index] = $phrase;
                }
                $phrase->addWord($this->words[$i]);
            }
        }
        $dependencyGroups = [];
        array_push($dependencyGroups, ...array_values($groups));
        return $dependencyGroups;
    }

    /**
     * The method constructs all possible shallow parse groups of a sentence.
     * @return array Shallow parse groups of a sentence.
     */
    public function getShallowParseGroups(): array
    {
        $shallowParseGroups = [];
        $previousWord = null;
        $current = null;
        for ($i = 0; $i < count($this->words); $i++) {
            $annotatedWord = $this->words[$i];
            if ($annotatedWord instanceof AnnotatedWord) {
                if ($previousWord === null) {
                    $current = new AnnotatedPhrase(1, $annotatedWord->getShallowParse());
                } else {
                    if ($previousWord->getShallowParse() != null && $previousWord->getShallowParse() != $annotatedWord->getShallowParse()) {
                        $shallowParseGroups[] = $current;
                        $current = new AnnotatedPhrase($i, $annotatedWord->getShallowParse());
                    }
                }
            }
            $current->addWord($annotatedWord);
            $previousWord = $annotatedWord;
        }
        $shallowParseGroups[] = $current;
        return $shallowParseGroups;
    }

    /**
     * The method checks all words in the sentence and returns true if at least one of the words is annotated with
     * PREDICATE tag.
     * @return bool True if at least one of the words is annotated with PREDICATE tag; false otherwise.
     */
    public function containsPredicate(): bool
    {
        foreach ($this->words as $word) {
            $annotatedWord = $word;
            if ($annotatedWord instanceof AnnotatedWord && $annotatedWord->getArgumentList() != null) {
                if ($annotatedWord->getArgumentList()->containsPredicate()) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * The method checks all words in the sentence and returns true if at least one of the words is annotated with
     * PREDICATE tag.
     * @return bool True if at least one of the words is annotated with PREDICATE tag; false otherwise.
     */
    public function containsFramePredicate(): bool
    {
        foreach ($this->words as $word) {
            $annotatedWord = $word;
            if ($annotatedWord instanceof AnnotatedWord && $annotatedWord->getFrameElementList() != null) {
                if ($annotatedWord->getFrameElementList()->containsPredicate()) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Replaces id's of predicates, which have previousId as synset id, with currentId. Replaces also predicate id's of
     * frame elements, which have predicate id's previousId, with currentId.
     * @param string $previousId Previous id of the synset.
     * @param string $currentId Replacement id.
     * @return bool Returns true, if any replacement has been done; false otherwise.
     */
    public function updateConnectedPredicate(string $previousId, string $currentId): bool
    {
        $modified = false;
        foreach ($this->words as $word) {
            $annotatedWord = $word;
            if ($annotatedWord instanceof AnnotatedWord) {
                if ($annotatedWord->getArgumentList() != null) {
                    $argumentList = $annotatedWord->getArgumentList();
                    if ($argumentList->containsPredicateWithId($previousId)) {
                        $argumentList->updateConnectedId($previousId, $currentId);
                        $modified = true;
                    }
                }
                if ($annotatedWord->getFrameElementList() != null) {
                    $frameElementList = $annotatedWord->getFrameElementList();
                    if ($frameElementList->containsPredicateWithId($previousId)) {
                        $frameElementList->updateConnectedId($previousId, $currentId);
                        $modified = true;
                    }
                }
            }
        }
        return $modified;
    }

    /**
     * The method returns all possible words, which is
     * 1. Verb
     * 2. Its semantic tag is assigned
     * 3. A frameset exists for that semantic tag
     * @param FramesetList $framesetList Frameset list that contains all frames for Turkish
     * @return array An array of words, which are verbs, semantic tags assigned, and framesetlist assigned for that tag.
     */
    public function predicateCandidates(FramesetList $framesetList): array
    {
        $candidateList = [];
        foreach ($this->words as $word) {
            $annotatedWord = $word;
            if ($annotatedWord instanceof AnnotatedWord && $annotatedWord->getParse() != null &&
                $annotatedWord->getParse()->isVerb() && $annotatedWord->getSemantic() != null &&
                $framesetList->frameExists($annotatedWord->getSemantic())) {
                $candidateList[] = $annotatedWord;
            }
        }
        for ($i = 0; $i < 2; $i++) {
            for ($j = 0; $j < count($this->words) - $i - 1; $j++) {
                $annotatedWord = $this->words[$j];
                $nextAnnotatedWord = $this->words[$j + 1];
                if (!in_Array($annotatedWord, $candidateList) && in_array($nextAnnotatedWord, $candidateList) &&
                    $annotatedWord->getSemantic() != null && $annotatedWord->getSemantic() == $nextAnnotatedWord->getSemantic()) {
                    $candidateList[] = $annotatedWord;
                }
            }
        }
        return $candidateList;
    }

    /**
     * The method returns all possible words, which is
     * 1. Verb
     * 2. Its semantic tag is assigned
     * 3. A frameset exists for that semantic tag
     * @param FrameNet $frameNet FrameNet list that contains all frames for Turkish
     * @return array An array of words, which are verbs, semantic tags assigned, and framenet assigned for that tag.
     */
    public function predicateFrameCandidates(FrameNet $frameNet): array
    {
        $candidateList = [];
        foreach ($this->words as $word) {
            $annotatedWord = $word;
            if ($annotatedWord instanceof AnnotatedWord && $annotatedWord->getParse() != null &&
                $annotatedWord->getParse()->isVerb() && $annotatedWord->getSemantic() != null &&
                $frameNet->lexicalUnitExists($annotatedWord->getSemantic())) {
                $candidateList[] = $annotatedWord;
            }
        }
        for ($i = 0; $i < 2; $i++) {
            for ($j = 0; $j < count($this->words) - $i - 1; $j++) {
                $annotatedWord = $this->words[$j];
                $nextAnnotatedWord = $this->words[$j + 1];
                if (!in_Array($annotatedWord, $candidateList) && in_array($nextAnnotatedWord, $candidateList) &&
                    $annotatedWord->getSemantic() != null && $annotatedWord->getSemantic() == $nextAnnotatedWord->getSemantic()) {
                    $candidateList[] = $annotatedWord;
                }
            }
        }
        return $candidateList;
    }

    /**
     * Returns the i'th predicate in the sentence.
     * @param int $index Predicate index
     * @return string The predicate with index index in the sentence.
     */
    public function getPredicate(int $index): string
    {
        $count1 = 0;
        $count2 = 0;
        $data = 0;
        $word = [];
        $parse = [];
        if ($index < $this->wordCount()) {
            for ($i = 0; $i < $this->wordCount(); $i++) {
                $word[] = $this->getWord($i);
                $parse[] = $word[$i]->getParse();
            }
            for ($i = $index; $i >= 0; $i--) {
                if ($parse[$i] != null && $parse[$i]->getRootPos() != null && $parse[$i]->getPos() != null &&
                    $parse[$i]->getRootPos() == "VERB" && $parse[$i]->getPos() == "VERB") {
                    $count1 = $index - $i;
                    break;
                }
            }
            for ($i = $index; $i < $this->wordCount() - $index; $i++) {
                if ($parse[$i] != null && $parse[$i]->getRootPos() != null && $parse[$i]->getPos() != null &&
                    $parse[$i]->getRootPos() == "VERB" && $parse[$i]->getPos() == "VERB") {
                    $count2 = $i - $index;
                    break;
                }
            }
            if ($count1 > $count2) {
                $data = $word[$count1]->getName();
            } else {
                $data = $word[$count2]->getName();
            }
        }
        return $data;
    }

    /**
     * Removes the i'th word from the sentence
     * @param int $index Word index
     */
    public function removeWord(int $index): void
    {
        foreach ($this->words as $value) {
            $word = $value;
            if ($word instanceof AnnotatedWord && $word->getUniversalDependency() != null) {
                if ($word->getUniversalDependency()->to() == $index + 1) {
                    $word->setUniversalDependency(-1, "ROOT");
                } else {
                    if ($word->getUniversalDependency()->to() > $index + 1) {
                        $word->setUniversalDependency($word->getUniversalDependency()->to() - 1, $word->getUniversalDependency()->__toString());
                    }
                }
            }
        }
        array_splice($this->words, $index, 1);
    }

    /**
     * The toStems method returns an accumulated string of each word's stems in words {@link Array}.
     * If the parse of the word does not exist, the method adds the surfaceform to the resulting string.
     *
     * @return string String result which has all the stems of each item in words {@link Array}.
     */
    public function toStems(): string
    {
        if (count($this->words) > 0) {
            $annotatedWord = $this->words[0];
            if ($annotatedWord instanceof AnnotatedWord) {
                if ($annotatedWord->getParse() != null) {
                    $result = $annotatedWord->getParse()->getWord()->getName();
                } else {
                    $result = $annotatedWord->getName();
                }
            }
            for ($i = 1; $i < count($this->words); $i++) {
                $annotatedWord = $this->words[$i];
                if ($annotatedWord instanceof AnnotatedWord) {
                    if ($annotatedWord->getParse() != null) {
                        $result .= " " . $annotatedWord->getParse()->getWord()->getName();
                    } else {
                        $result .= " " . $annotatedWord->getName();
                    }
                }
            }
            return $result;
        } else {
            return "";
        }
    }

    /**
     * Compares the sentence with the given sentence and returns a parser evaluation score for this comparison. The result
     * is calculated by summing up the parser evaluation scores of word by word dpendency relation comparisons.
     * @param AnnotatedSentence $annotatedSentence Sentence to be compared.
     * @return ParserEvaluationScore A parser evaluation score object.
     */
    public function compareParses(AnnotatedSentence $annotatedSentence): ParserEvaluationScore
    {
        $score = new ParserEvaluationScore();
        for ($i = 0; $i < count($this->words); $i++) {
            $relation1 = $this->words[$i]->getUniversalDependency();
            $relation2 = $annotatedSentence->getWord($i)->getUniversalDependency();
            if ($relation1 instanceof UniversalDependencyRelation && $relation2 instanceof UniversalDependencyRelation) {
                $score->add($relation1->compareRelations($relation2));
            }
        }
        return $score;
    }

    /**
     * Returns the connlu format of the sentence with appended prefix string based on the path.
     * @param string|null $path Path of the sentence.
     * @return string The connlu format of the sentence with appended prefix string based on the path.
     */
    public function getUniversalDependencyFormat(?string $path): string
    {
        if ($path != null) {
            $result = "# sent_id = " . $path . $this->getFileName() . "\n" . "# text = " . $this->toWords() . "\n";
        } else {
            $result = "# sent_id = " . $this->getFileName() . "\n" . "# text = " . $this->toWords() . "\n";
        }
        for ($i = 0; $i < $this->wordCount(); $i++) {
            $word = $this->getWord($i);
            if ($word instanceof AnnotatedWord) {
                $result .= ($i + 1) . "\t" . $word->getUniversalDependencyFormat($this->wordCount()) . "\n";
            }
        }
        $result .= "\n";
        return $result;
    }

    /**
     * Creates a list of literal candidates for the i'th word in the sentence. It combines the results of
     * 1. All possible root forms of the i'th word in the sentence
     * 2. All possible 2-word expressions containing the i'th word in the sentence
     * 3. All possible 3-word expressions containing the i'th word in the sentence
     * @param WordNet $wordNet Turkish wordnet
     * @param FsmMorphologicalAnalyzer $fsm Turkish morphological analyzer
     * @param int $wordIndex Word index
     * @return array List of literal candidates containing all possible root forms and multiword expressions.
     */
    public function constructLiterals(WordNet $wordNet, FsmMorphologicalAnalyzer $fsm, int $wordIndex): array
    {
        /** @var AnnotatedWord $word */
        $word = $this->getWord($wordIndex);
        $morphologicalParse = $word->getParse();
        $metamorphicParse = $word->getMetamorphicParse();
        $possibleLiterals = $wordNet->constructLiterals($morphologicalParse->getWord()->getName(), $morphologicalParse, $metamorphicParse, $fsm);
        /** @var AnnotatedWord $firstSucceedingWord */
        $firstSucceedingWord = null;
        /** @var AnnotatedWord $secondSucceedingWord */
        $secondSucceedingWord = null;
        if ($this->wordCount() > $wordIndex + 1) {
            $firstSucceedingWord = $this->getWord($wordIndex + 1);
            if ($this->wordCount() > $wordIndex + 2) {
                $secondSucceedingWord = $this->getWord($wordIndex + 2);
            }
        }
        if ($firstSucceedingWord != null) {
            if ($secondSucceedingWord != null) {
                array_push($possibleLiterals, ...$wordNet->constructIdiomLiterals($fsm,
                    $word->getParse(), $word->getMetamorphicParse(),
                    $firstSucceedingWord->getParse(), $firstSucceedingWord->getMetamorphicParse(),
                    $secondSucceedingWord->getParse(), $secondSucceedingWord->getMetamorphicParse()));
            }
            array_push($possibleLiterals, ...$wordNet->constructIdiomLiterals($fsm,
                $word->getParse(), $word->getMetamorphicParse(),
                $firstSucceedingWord->getParse(), $firstSucceedingWord->getMetamorphicParse()));
        }
        return $possibleLiterals;
    }

    public function constructSynSets(WordNet $wordNet, FsmMorphologicalAnalyzer $fsm, int $wordIndex): array
    {
        /** @var AnnotatedWord $word */
        $word = $this->getWord($wordIndex);
        $morphologicalParse = $word->getParse();
        $metamorphicParse = $word->getMetamorphicParse();
        $possibleSynSets = $wordNet->constructSynSets($morphologicalParse->getWord()->getName(), $morphologicalParse, $metamorphicParse, $fsm);
        /** @var AnnotatedWord $firstPrecedingWord */
        $firstPrecedingWord = null;
        /** @var AnnotatedWord $secondPrecedingWord */
        $secondPrecedingWord = null;
        /** @var AnnotatedWord $firstSucceedingWord */
        $firstSucceedingWord = null;
        /** @var AnnotatedWord $secondSucceedingWord */
        $secondSucceedingWord = null;
        if ($wordIndex > 0){
            $firstPrecedingWord = $this->getWord($wordIndex - 1);
            if ($wordIndex > 1) {
                $secondPrecedingWord = $this->getWord($wordIndex - 2);
            }
        }
        if ($this->wordCount() > $wordIndex + 1) {
            $firstSucceedingWord = $this->getWord($wordIndex + 1);
            if ($this->wordCount() > $wordIndex + 2) {
                $secondSucceedingWord = $this->getWord($wordIndex + 2);
            }
        }
        if ($firstPrecedingWord != null) {
            if ($secondPrecedingWord != null) {
                array_push($possibleSynSets, ...$wordNet->constructIdiomSynSets($fsm,
                    $secondPrecedingWord->getParse(), $secondPrecedingWord->getMetamorphicParse(),
                    $firstPrecedingWord->getParse(), $firstPrecedingWord->getMetamorphicParse(),
                    $word->getParse(), $word->getMetamorphicParse()));
            }
            array_push($possibleSynSets, ...$wordNet->constructIdiomSynSets($fsm,
                $firstSucceedingWord->getParse(), $firstSucceedingWord->getMetamorphicParse(),
                $word->getParse(), $word->getMetamorphicParse()));
        }
        if ($firstPrecedingWord != null && $firstSucceedingWord != null) {
            array_push($possibleSynSets, ...$wordNet->constructIdiomSynSets($fsm,
                $firstPrecedingWord->getParse(), $firstPrecedingWord->getMetamorphicParse(),
                $word->getParse(), $word->getMetamorphicParse(),
                $firstSucceedingWord->getParse(), $firstSucceedingWord->getMetamorphicParse()));
        }
        if ($firstSucceedingWord != null) {
            if ($secondSucceedingWord != null) {
                array_push($possibleSynSets, ...$wordNet->constructIdiomSynSets($fsm,
                    $word->getParse(), $word->getMetamorphicParse(),
                    $firstSucceedingWord->getParse(), $firstSucceedingWord->getMetamorphicParse(),
                    $secondSucceedingWord->getParse(), $secondSucceedingWord->getMetamorphicParse()));
            }
            array_push($possibleSynSets[], ...$wordNet->constructIdiomSynSets($fsm,
                $word->getParse(), $word->getMetamorphicParse(),
                $firstSucceedingWord->getParse(), $firstSucceedingWord->getMetamorphicParse()));
        }
        return $possibleSynSets;
    }

}