<?php

use olcaytaner\AnnotatedSentence\AnnotatedSentence;
use olcaytaner\Propbank\FramesetList;
use PHPUnit\Framework\TestCase;

class AnnotatedSentenceTest extends TestCase
{
    public function testAnnotatedSentence(){
        $frameList = new FramesetList();
        $sentence0 = new AnnotatedSentence("../sentences/0000.dev");
        $this->assertCount(3, $sentence0->getDependencyGroups(11));
        $this->assertCount(4, $sentence0->getShallowParseGroups());
        $this->assertTrue($sentence0->containsPredicate());
        $this->assertEquals("bulandırdı", $sentence0->getPredicate(0));
        $this->assertEquals("devasa ölçek yeni kanun kullan karmaşık ve çetrefil dil kavga bulan .", $sentence0->toStems());
        $this->assertCount(1, $sentence0->predicateCandidates($frameList));
    }
}