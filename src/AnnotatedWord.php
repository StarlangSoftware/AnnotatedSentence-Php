<?php

namespace olcaytaner\AnnotatedSentence;

use olcaytaner\DependencyParser\Universal\UniversalDependencyRelation;
use olcaytaner\Dictionary\Dictionary\Word;
use olcaytaner\Framenet\FrameElementList;
use olcaytaner\MorphologicalAnalysis\MorphologicalAnalysis\FsmParse;
use olcaytaner\MorphologicalAnalysis\MorphologicalAnalysis\MetamorphicParse;
use olcaytaner\MorphologicalAnalysis\MorphologicalAnalysis\MorphologicalParse;
use olcaytaner\MorphologicalAnalysis\MorphologicalAnalysis\MorphologicalTag;
use olcaytaner\NamedEntityRecognition\Gazetteer;
use olcaytaner\NamedEntityRecognition\NamedEntityType;
use olcaytaner\NamedEntityRecognition\NamedEntityTypeStatic;
use olcaytaner\NamedEntityRecognition\Slot;
use olcaytaner\Propbank\ArgumentList;
use olcaytaner\Sentinet\PolarityType;
use Transliterator;

class AnnotatedWord extends Word
{
    /**
     * In order to add another layer, do the following:
     * 1. Select a name for the layer.
     * 2. Add a new constant to ViewLayerType.
     * 3. Add private attribute.
     * 4. Add an if-else to the constructor, where you set the private attribute with the layer name.
     * 5. Update toString method.
     * 6. Add initial value to the private attribute in other constructors.
     * 7. Update getLayerInfo.
     * 8. Add getter and setter methods.
     */
    private ?MorphologicalParse $parse = null;
    private ?MetamorphicParse $metamorphicParse = null;
    private ?string $semantic = null;
    private ?NamedEntityType $namedEntityType = null;
    private ?ArgumentList $argumentList = null;
    private ?FrameElementList $frameElementList = null;
    private ?UniversalDependencyRelation $universalDependency = null;
    private ?string $shallowParse = null;
    private ?PolarityType $polarity = null;
    private ?Slot $slot = null;
    private ?string $ccg = null;
    private ?string $posTag = null;
    private Language $language = Language::TURKISH;

    public function __construct(string $word, mixed $second = null)
    {
        parent::__construct("");
        if ($second === null) {
            $splitLayers = preg_split("/[{}]/", $word);
            foreach ($splitLayers as $layer) {
                if ($layer == "")
                    continue;
                if (!str_contains($layer, "=")) {
                    $this->name = $layer;
                    continue;
                }
                $layerType = mb_substr($layer, 0, mb_strpos($layer, "="));
                $layerValue = mb_substr($layer, mb_strpos($layer, "=") + 1);
                if ($layerType == "turkish" || $layerType == "english"
                    || $layerType == "persian") {
                    $this->name = $layerValue;
                    $this->language = $this->getLanguageFromString($layerType);
                } else {
                    if ($layerType == "morphologicalAnalysis") {
                        $this->parse = new MorphologicalParse($layerValue);
                    } else {
                        if ($layerType == "metaMorphemes") {
                            $this->metamorphicParse = new MetamorphicParse($layerValue);
                        } else {
                            if ($layerType == "semantics") {
                                $this->semantic = $layerValue;
                            } else {
                                if ($layerType == "namedEntity") {
                                    $this->namedEntityType = NamedEntityTypeStatic::getNamedEntityType($layerValue);
                                } else {
                                    if ($layerType == "propbank") {
                                        $this->argumentList = new ArgumentList($layerValue);
                                    } else {
                                        if ($layerType == "shallowParse") {
                                            $this->shallowParse = $layerValue;
                                        } else {
                                            if ($layerType == "universalDependency") {
                                                $values = explode( "$", $layerValue);
                                                $this->universalDependency = new UniversalDependencyRelation((int)$values[0], $values[1]);
                                            } else {
                                                if ($layerType == "framenet") {
                                                    $this->frameElementList = new FrameElementList($layerValue);
                                                } else {
                                                    if ($layerType == "slot") {
                                                        $this->slot = new Slot($layerValue);
                                                    } else {
                                                        if ($layerType == "polarity") {
                                                            $this->setPolarity($layerValue);
                                                        } else {
                                                            if ($layerType == "ccg") {
                                                                $this->ccg = $layerValue;
                                                            } else {
                                                                if ($layerType == "posTag") {
                                                                    $this->posTag = $layerValue;
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } else {
            if ($second instanceof FsmParse) {
                $this->parse = $second;
                $this->setMetamorphicParse($second->getWithList());
                $this->namedEntityType = NamedEntityType::NONE;
            } else {
                if ($second instanceof MorphologicalParse) {
                    $this->parse = $second;
                    $this->namedEntityType = NamedEntityType::NONE;
                } else {
                    $this->namedEntityType = $second;
                }
            }
        }
    }

    /**
     * Converts an {@link AnnotatedWord} to string. For each annotation layer, the method puts a left brace, layer name,
     * equal sign and layer value finishing with right brace.
     * @return string String form of the {@link AnnotatedWord}.
     */
    public function __toString(): string
    {
        $result = "";
        switch ($this->language) {
            case Language::TURKISH:
                $result = "{turkish=" . $this->name . "}";
                break;
            case Language::ENGLISH:
                $result = "{english=" . $this->name . "}";
                break;
            case Language::PERSIAN:
                $result = "{persian=" . $this->name . "}";
                break;
        }
        if ($this->parse != null) {
            $result = $result . "{morphologicalAnalysis=" . $this->parse->toString__() . "}";
        }
        if ($this->metamorphicParse != null) {
            $result = $result . "{metaMorphemes=" . $this->metamorphicParse->__toString() . "}";
        }
        if ($this->semantic != null) {
            $result = $result . "{semantics=" . $this->semantic . "}";
        }
        if ($this->namedEntityType != null) {
            $result = $result . "{namedEntity=" . NamedEntityTypeStatic::getNamedEntity($this->namedEntityType) . "}";
        }
        if ($this->argumentList != null) {
            $result = $result . "{propbank=" . $this->argumentList->__toString() . "}";
        }
        if ($this->frameElementList != null) {
            $result = $result . "{framenet=" . $this->frameElementList->__toString() . "}";
        }
        if ($this->shallowParse != null) {
            $result = $result . "{shallowParse=" . $this->shallowParse . "}";
        }
        if ($this->universalDependency != null) {
            $result = $result . "{universalDependency=" . $this->universalDependency->to() . "$"
                . $this->universalDependency->__toString() . "}";
        }
        if ($this->slot != null) {
            $result = $result . "{slot=" . $this->slot->__toString() . "}";
        }
        if ($this->polarity != null) {
            $result = $result . "{polarity=" . $this->getPolarityString() . "}";
        }
        if ($this->ccg != null) {
            $result = $result . "{ccg=" . $this->ccg . "}";
        }
        if ($this->posTag != null) {
            $result = $result . "{posTag=" . $this->posTag . "}";
        }
        return $result;
    }

    /**
     * Returns the value of a given layer.
     * @param ViewLayerType $viewLayerType Layer for which the value questioned.
     * @return string|null The value of the given layer.
     */
    public function getLayerInfo(ViewLayerType $viewLayerType): ?string
    {
        switch ($viewLayerType) {
            case ViewLayerType::INFLECTIONAL_GROUP:
                if ($this->parse != null) {
                    return $this->parse->toString__();
                }
                break;
            case ViewLayerType::META_MORPHEME:
                if ($this->metamorphicParse != null) {
                    return $this->metamorphicParse->__toString();
                }
                break;
            case ViewLayerType::SEMANTICS:
                return $this->semantic;
            case ViewLayerType::NER:
                if ($this->namedEntityType != null) {
                    return $this->namedEntityType->__toString();
                }
                break;
            case ViewLayerType::SHALLOW_PARSE:
                return $this->shallowParse;
            case ViewLayerType::TURKISH_WORD:
                return $this->name;
            case ViewLayerType::PROPBANK:
                if ($this->argumentList != null) {
                    return $this->argumentList->__toString();
                }
                break;
            case ViewLayerType::DEPENDENCY:
                if ($this->universalDependency != null) {
                    return $this->universalDependency->to() . "$" . $this->universalDependency->__toString();
                }
                break;
            case ViewLayerType::FRAMENET:
                if ($this->frameElementList != null) {
                    return $this->frameElementList->__toString();
                }
                break;
            case ViewLayerType::SLOT:
                if ($this->slot != null) {
                    return $this->slot->__toString();
                }
                break;
            case ViewLayerType::POLARITY:
                if ($this->polarity != null) {
                    return $this->getPolarityString();
                }
                break;
            case ViewLayerType::CCG:
                if ($this->ccg != null) {
                    return $this->ccg;
                }
                break;
            case ViewLayerType::POS_TAG:
                if ($this->posTag != null) {
                    return $this->posTag;
                }
                break;
        }
        return null;
    }

    /**
     * Returns the morphological parse layer of the word.
     * @return MorphologicalParse The morphological parse of the word.
     */
    public function getParse(): MorphologicalParse
    {
        return $this->parse;
    }

    /**
     * Sets the morphological parse layer of the word.
     * @param string|null $parseString The new morphological parse of the word in string form.
     */
    public function setParse(?string $parseString): void
    {
        if ($parseString != null) {
            $this->parse = new MorphologicalParse($parseString);
        } else {
            $this->parse = null;
        }
    }

    /**
     * Returns the metamorphic parse layer of the word.
     * @return MetamorphicParse The metamorphic parse of the word.
     */
    public function getMetamorphicParse(): MetamorphicParse
    {
        return $this->metamorphicParse;
    }

    /**
     * Sets the metamorphic parse layer of the word.
     * @param string $parseString The new metamorphic parse of the word in string form.
     */
    public function setMetamorphicParse(string $parseString): void
    {
        $this->metamorphicParse = new MetamorphicParse($parseString);
    }

    /**
     * Returns the semantic layer of the word.
     * @return string Sense id of the word.
     */
    public function getSemantic(): string
    {
        return $this->semantic;
    }

    /**
     * Sets the semantic layer of the word.
     * @param string $semantic New sense id of the word.
     */
    public function setSemantic(string $semantic): void
    {
        $this->semantic = $semantic;
    }

    /**
     * Returns the named entity layer of the word.
     * @return NamedEntityType Named entity tag of the word.
     */
    public function getNamedEntityType(): NamedEntityType
    {
        return $this->namedEntityType;
    }

    /**
     * Sets the named entity layer of the word.
     * @param string|null $namedEntity New named entity tag of the word.
     */
    public function setNamedEntityType(?string $namedEntity): void
    {
        if ($namedEntity != null) {
            $this->namedEntityType = NamedEntityTypeStatic::getNamedEntityType($namedEntity);
        } else {
            $this->namedEntityType = null;
        }
    }

    /**
     * Returns the semantic role layer of the word.
     * @return ArgumentList Semantic role tag of the word.
     */
    public function getArgumentList(): ArgumentList
    {
        return $this->argumentList;
    }

    /**
     * Sets the semantic role layer of the word.
     * @param string|null $argumentList New semantic role tag of the word.
     */
    public function setArgumentList(?string $argumentList): void
    {
        if ($argumentList != null) {
            $this->argumentList = new ArgumentList($argumentList);
        } else {
            $this->argumentList = null;
        }
    }

    /**
     * Returns the frameNet layer of the word.
     * @return FrameElementList FrameNet tag of the word.
     */
    public function getFrameElementList(): FrameElementList
    {
        return $this->frameElementList;
    }

    /**
     * Sets the framenet layer of the word.
     * @param string|null $frameElementList New frame element tag of the word.
     */
    public function setFrameElementList(?string $frameElementList): void
    {
        if ($frameElementList != null) {
            $this->frameElementList = new FrameElementList($frameElementList);
        } else {
            $this->frameElementList = null;
        }
    }

    /**
     * Returns the slot filling layer of the word.
     * @return Slot Slot tag of the word.
     */
    public function getSlot(): Slot
    {
        return $this->slot;
    }

    /**
     * Sets the slot filling layer of the word.
     * @param string|null $slot New slot tag of the word.
     */
    public function setSlot(?string $slot): void
    {
        if ($slot != null) {
            $this->slot = new Slot($slot);
        } else {
            $this->slot = null;
        }
    }

    /**
     * Returns the polarity layer of the word.
     * @return PolarityType Polarity tag of the word.
     */
    public function getPolarity(): PolarityType
    {
        return $this->polarity;
    }

    /**
     * Returns the polarity layer of the word.
     * @return string Polarity string of the word.
     */
    public function getPolarityString(): string
    {
        switch ($this->polarity) {
            case PolarityType::POSITIVE:
                return "positive";
            case PolarityType::NEGATIVE:
                return "negative";
            default:
                return "neutral";
        }
    }

    /**
     * Sets the polarity layer of the word.
     * @param string|null $polarity New polarity tag of the word.
     */
    public function setPolarity(?string $polarity): void
    {
        if ($polarity != null) {
            switch (strtolower($polarity)) {
                case "positive":
                case "pos":
                    $this->polarity = PolarityType::POSITIVE;
                    break;
                case "negative":
                case "neg":
                    $this->polarity = PolarityType::NEGATIVE;
                    break;
                default:
                    $this->polarity = PolarityType::NEUTRAL;
            }
        } else {
            $this->polarity = null;
        }
    }

    /**
     * Returns the shallow parse layer of the word.
     * @return string Shallow parse tag of the word.
     */
    public function getShallowParse(): ?string
    {
        return $this->shallowParse;
    }

    /**
     * Sets the shallow parse layer of the word.
     * @param string $shallowParse New shallow parse tag of the word.
     */
    public function setShallowParse(string $shallowParse): void
    {
        $this->shallowParse = $shallowParse;
    }

    /**
     * Returns the universal dependency layer of the word.
     * @return UniversalDependencyRelation Universal dependency relation of the word.
     */
    public function getUniversalDependency(): UniversalDependencyRelation
    {
        return $this->universalDependency;
    }

    /**
     * Sets the universal dependency layer of the word.
     * @param int $to Word related to.
     * @param string $dependencyType type of dependency the word is related to.
     */
    public function setUniversalDependency(int $to, string $dependencyType): void
    {
        if ($to < 0) {
            $this->universalDependency = null;
        } else {
            $this->universalDependency = new UniversalDependencyRelation($to, $dependencyType);
        }
    }

    /**
     * Returns the universal pos of the word.
     * @return string|null If the language is Turkish, it directly calls getUniversalDependencyPos of the parse. If the language
     * is English, it returns pos according to the Penn tag of the current word.
     */
    public function getUniversalDependencyPos(): ?string
    {
        if ($this->language == Language::TURKISH && $this->parse != null) {
            return $this->parse->getUniversalDependencyPos();
        } else {
            if ($this->language == Language::ENGLISH && $this->posTag != null) {
                switch ($this->posTag) {
                    case "#":
                    case "$":
                    case "SYM":
                        return "SYM";
                    case "\"":
                    case ",":
                    case "-LRB-":
                    case "-RRB-":
                    case ".":
                    case ":":
                    case "``":
                    case "HYPH":
                        return "PUNCT";
                    case "AFX":
                    case "JJ":
                    case "JJR":
                    case "JJS":
                        return "ADJ";
                    case "CC":
                        return "CCONJ";
                    case "CD":
                        return "NUM";
                    case "DT":
                    case "PDT":
                    case "PRP$":
                    case "WDT":
                    case "WP$":
                        return "DET";
                    case "IN":
                    case "RP":
                        return "ADP";
                    case "FW":
                    case "LS":
                    case "NIL":
                        return "X";
                    case "VB":
                    case "VBD":
                    case "VBG":
                    case "VBN":
                    case "VBP":
                    case "VBZ":
                        return "VERB";
                    case "MD":
                    case "AUX:VB":
                    case "AUX:VBD":
                    case "AUX:VBG":
                    case "AUX:VBN":
                    case "AUX:VBP":
                    case "AUX:VBZ":
                        return "AUX";
                    case "NN":
                    case "NNS":
                        return "NOUN";
                    case "NNP":
                    case "NNPS":
                        return "PROPN";
                    case "POS":
                    case "TO":
                        return "PART";
                    case "EX":
                    case "PRP":
                    case "WP":
                        return "PRON";
                    case "RB":
                    case "RBR":
                    case "RBS":
                    case "WRB":
                        return "ADV";
                    case "UH":
                        return "INTJ";
                }
            }
        }
        return null;
    }

    /**
     * Returns the $features of the universal dependency relation of the current word.
     * @return array If the language is Turkish, it calls getUniversalDependencyFeatures of the parse. If the language is
     * English, it returns dependency $features according to the Penn tag of the current word.
     */
    public function getUniversalDependencyFeatures(): array
    {
        $featureList = [];
        if ($this->language == Language::TURKISH && $this->parse != null){
            return $this->parse->getUniversalDependencyFeatures($this->parse->getUniversalDependencyPos());
        } else {
            if ($this->language == Language::ENGLISH && $this->posTag != null) {
                switch ($this->posTag){
                    case "\"":
                        $featureList[] = "PunctSide=Fin";
                        $featureList[] = "PunctType=Quot";
                        break;
                    case ",":
                        $featureList[] = "PunctType=Comm";
                        break;
                    case "-LRB-":
                        $featureList[] = "PunctSide=Ini";
                        $featureList[] = "PunctType=Brck";
                        break;
                    case "-RRB-":
                        $featureList[] = "PunctSide=Fin";
                        $featureList[] = "PunctType=Brck";
                        break;
                    case ".":
                        $featureList[] = "PunctType=Peri";
                        break;
                    case "``":
                        $featureList[] = "PunctSide=Ini";
                        $featureList[] = "PunctType=Quot";
                        break;
                    case "HYPH":
                        $featureList[] = "PunctType=Dash";
                        break;
                    case "AFX":
                        $featureList[] = "Hyph=Yes";
                        break;
                    case "JJ":
                    case "RB":
                        $featureList[] = "Degree=Pos";
                        break;
                    case "JJR":
                    case "RBR":
                        $featureList[] = "Degree=Cmp";
                        break;
                    case "JJS":
                    case "RBS":
                        $featureList[] = "Degree=Sup";
                        break;
                    case "CD":
                        $featureList[] = "NumType=Card";
                        break;
                    case "DT":
                        $featureList[] = "PronType=Art";
                        break;
                    case "PDT":
                        $featureList[] = "AdjType=Pdt";
                        break;
                    case "PRP$":
                        $featureList[] = "Poss=Yes";
                        $featureList[] = "PronType=Prs";
                        break;
                    case "WDT":
                    case "WP":
                    case "WRB":
                        $featureList[] = "PronType=Int,Rel";
                        break;
                    case "WP$":
                        $featureList[] = "Poss=Yes";
                        $featureList[] = "PronType=Int,Rel";
                        break;
                    case "TO":
                    case "POS":
                    case "MD":
                    case "RP":
                        break;
                    case "FW":
                        $featureList[] = "Foreign=Yes";
                        break;
                    case "LS":
                        $featureList[] = "NumType=Ord";
                        break;
                    case "VB":
                    case "AUX:VB":
                        $featureList[] = "VerbForm=Inf";
                        break;
                    case "VBD":
                    case "AUX:VBD":
                        $featureList[] = "Mood=Ind";
                        $featureList[] = "Tense=Past";
                        $featureList[] = "VerbForm=Fin";
                        break;
                    case "VBG":
                    case "AUX:VBG":
                        $featureList[] = "Tense=Pres";
                        $featureList[] = "VerbForm=Part";
                        break;
                    case "VBN":
                    case "AUX:VBN":
                        $featureList[] = "Tense=Past";
                        $featureList[] = "VerbForm=Part";
                        break;
                    case "VBP":
                    case "AUX:VBP":
                        $featureList[] = "Mood=Ind";
                        $featureList[] = "Tense=Pres";
                        $featureList[] = "VerbForm=Fin";
                        break;
                    case "VBZ":
                    case "AUX:VBZ":
                        $featureList[] = "Mood=Ind";
                        $featureList[] = "Number=Sing";
                        $featureList[] = "Person=3";
                        $featureList[] = "Tense=Pres";
                        $featureList[] = "VerbForm=Fin";
                        break;
                    case "NN":
                    case "NNP":
                        $featureList[] = "Number=Sing";
                        break;
                    case "NNS":
                    case "NNPS":
                        $featureList[] = "Number=Plur";
                        break;
                    case "EX":
                        $featureList[] = "PronType=Dem";
                        break;
                    case "PRP":
                        $featureList[] = "PronType=Prs";
                        break;
                }
            }
        }
        return $featureList;
    }

    /**
     * Returns the connlu format string for this word. Adds surface form, root, universal pos tag, features, and
     * universal dependency information.
     * @param int $sentenceLength Number of words in the sentence.
     * @return string The connlu format string for this word.
     */
    public function getUniversalDependencyFormat(int $sentenceLength): string{
        $uPos = $this->getUniversalDependencyPos();
        if ($uPos != null){
            switch ($this->language){
                case Language::TURKISH:
                default:
                    $result = $this->name . "\t" . $this->parse->getWord()->getName() . "\t" . $uPos . "\t_\t";
                    break;
                case Language::ENGLISH:
                    if ($this->metamorphicParse != null){
                        $result = $this->name . "\t" . $this->metamorphicParse->getWord()->getName() . "\t" . $uPos . "\t_\t";
                    } else {
                        $result = $this->name . "\t" . $this->name . "\t" . $uPos . "\t_\t";
                    }
                    break;
            }
            $features = $this->getUniversalDependencyFeatures();
            if (count($features) == 0){
                $result = $result . "_";
            } else {
                $first = true;
                foreach ($features as $feature){
                    if ($first){
                        $first = false;
                    } else {
                        $result .= "|";
                    }
                    $result .= $feature;
                }
            }
            $result .= "\t";
            if ($this->universalDependency != null && $this->universalDependency->to() <= $sentenceLength){
                $result .= $this->universalDependency->to() . "\t" . strtolower($this->universalDependency->__toString()) . "\t";
            } else {
                $result .= "_\t_\t";
            }
            $result .= "_\t_";
            return $result;
        } else {
            return $this->name . "\t" . $this->name . "\t_\t_\t_\t_\t_\t_\t_";
        }
    }

    /**
     * Returns the CCG layer of the word.
     * @return string CCG string of the word.
     */
    public function getCcg(): string{
        return $this->ccg;
    }

    /**
     * Sets the CCG layer of the word.
     * @param string $ccg New CCG of the word.
     */
    public function setCcg(string $ccg): void{
        $this->ccg = $ccg;
    }

    /**
     * Returns the posTag layer of the word.
     * @return string posTag string of the word.
     */
    public function getPosTag(): string{
        return $this->posTag;
    }

    /**
     * Sets the posTag layer of the word.
     * @param string $posTag New posTag of the word.
     */
    public function setPosTag(string $posTag): void{
        $this->posTag = $posTag;
    }

    /**
     * Checks the gazetteer and sets the named entity tag accordingly.
     * @param Gazetteer $gazetteer Gazetteer used to set named entity tag.
     */
    public function checkGazetteer(Gazetteer $gazetteer): void{
        $wordLowercase = Transliterator::create("tr-Lower")->transliterate($this->name);
        if ($gazetteer->contains($wordLowercase) && $this->parse->containsTag(MorphologicalTag::PROPERNOUN)){
            $this->setNamedEntityType($gazetteer->getName());
        }
        if (str_contains($wordLowercase, "'") && $gazetteer->contains(mb_substr($wordLowercase, 0, mb_strpos($wordLowercase, "'"))) &&
            $this->parse->containsTag(MorphologicalTag::PROPERNOUN)){
            $this->setNamedEntityType($gazetteer->getName());
        }
    }

    /**
     * Converts a language string to language.
     * @param string $languageString String defining the language name.
     * @return Language Language corresponding to the languageString.
     */
    public function getLanguageFromString(string $languageString): Language{
        switch ($languageString){
            case "turkish":
            case "Turkish":
                return Language::TURKISH;
            case "english":
            case "English":
                return Language::ENGLISH;
            case "persian":
            case "Persian":
                return Language::PERSIAN;
        }
        return Language::TURKISH;
    }

    /**
     * Returns the language of the word.
     * @return Language The language of the word.
     */
    public function getLanguage(): Language{
        return $this->language;
    }
}