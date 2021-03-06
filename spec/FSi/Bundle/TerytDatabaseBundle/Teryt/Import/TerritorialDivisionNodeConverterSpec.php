<?php

namespace spec\FSi\Bundle\TerytDatabaseBundle\Teryt\Import;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use FSi\Bundle\TerytDatabaseBundle\Entity\Community;
use FSi\Bundle\TerytDatabaseBundle\Entity\CommunityType;
use FSi\Bundle\TerytDatabaseBundle\Entity\District;
use FSi\Bundle\TerytDatabaseBundle\Entity\Province;
use FSi\Bundle\TerytDatabaseBundle\Exception\TerritorialDivisionNodeConverterException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class TerritorialDivisionNodeConverterSpec extends ObjectBehavior
{
    function let(ObjectManager $om, ObjectRepository $or)
    {
        // It is not possible to mock internal classes with final constructor
        $this->beConstructedWith(new \SimpleXMLElement('<row></row>'), $om);
        $om->getRepository(Argument::type('string'))->willReturn($or);
        $or->findOneBy(Argument::type('array'))->willReturn();
    }

    function it_converts_node_to_province(ObjectManager $om, ObjectRepository $or)
    {
        $xml = <<<EOT
<row>
    <col name="WOJ">02</col>
    <col name="POW"/>
    <col name="GMI"/>
    <col name="RODZ"/>
    <col name="NAZWA">DOLNOŚLĄSKIE</col>
    <col name="NAZDOD">województwo</col>
    <col name="STAN_NA">2013-01-01</col>
</row>
EOT;

        $expectedProvince = new Province();
        $expectedProvince->setCode('02')
            ->setName('DOLNOŚLĄSKIE');

        $this->beConstructedWith(new \SimpleXMLElement($xml), $om);
        $this->convertToEntity()->shouldBeLike($expectedProvince);
    }

    function it_converts_node_to_province_with_updating_existing_one(
        ObjectManager $om, ObjectRepository $or, Province $province
    ) {
        $xml = <<<EOT
<row>
    <col name="WOJ">02</col>
    <col name="POW"/>
    <col name="GMI"/>
    <col name="RODZ"/>
    <col name="NAZWA">Dolnośląskie</col>
    <col name="NAZDOD">województwo</col>
    <col name="STAN_NA">2013-01-01</col>
</row>
EOT;
        $om->getRepository('FSiTerytDbBundle:Province')
            ->shouldBeCalled()
            ->willReturn($or);

        $or->findOneBy(array(
            'code' => '02'
        ))->shouldBeCalled()->willReturn($province);

        $province->setName('Dolnośląskie')->shouldBeCalled();
        $province->setCode('02')->shouldNotBeCalled();

        $this->beConstructedWith(new \SimpleXMLElement($xml), $om);
        $this->convertToEntity()->shouldBeLike($province->getWrappedObject());
    }

    function it_converts_node_to_district(ObjectManager $om, ObjectRepository $or)
    {
        $xml = <<<EOT
<row>
    <col name="WOJ">02</col>
    <col name="POW">01</col>
    <col name="GMI"/>
    <col name="RODZ"/>
    <col name="NAZWA">bolesławiecki</col>
    <col name="NAZDOD">powiat</col>
    <col name="STAN_NA">2013-01-01</col>
</row>
EOT;
        $province = new Province();
        $province->setCode('02');
        $or->findOneBy(array(
            'code' => '02'
        ))->shouldBeCalled()->willReturn($province);

        $expectedDistrict = new District();
        $expectedDistrict->setCode('0201')
            ->setName('bolesławiecki')
            ->setProvince($province);

        $this->beConstructedWith(new \SimpleXMLElement($xml), $om);
        $this->convertToEntity()->shouldBeLike($expectedDistrict);
    }

    function it_converts_node_to_district_with_updating_existing_one(
        ObjectManager $om, ObjectRepository $or, District $district
    ) {
        $xml = <<<EOT
<row>
    <col name="WOJ">02</col>
    <col name="POW">01</col>
    <col name="GMI"/>
    <col name="RODZ"/>
    <col name="NAZWA">bolesławiecki</col>
    <col name="NAZDOD">powiat</col>
    <col name="STAN_NA">2013-01-01</col>
</row>
EOT;

        $or->findOneBy(array(
            'code' => '0201'
        ))->shouldBeCalled()->willReturn($district);

        $province = new Province();
        $province->setCode('02');
        $or->findOneBy(array(
            'code' => '02'
        ))->shouldBeCalled()->willReturn($province);

        $district->setName('bolesławiecki')->shouldBeCalled()->willReturn($district);
        $district->setProvince($province)->shouldBeCalled()->willReturn($district);
        $district->setCode('0201')->shouldNotBeCalled();

        $this->beConstructedWith(new \SimpleXMLElement($xml), $om);
        $this->convertToEntity()->shouldBeLike($district->getWrappedObject());
    }

    function it_converts_node_to_community(ObjectManager $om, ObjectRepository $or)
    {
        $xml = <<<EOT
<row>
    <col name="WOJ">02</col>
    <col name="POW">01</col>
    <col name="GMI">01</col>
    <col name="RODZ">1</col>
    <col name="NAZWA">Bolesławiec</col>
    <col name="NAZDOD">gmina miejska</col>
    <col name="STAN_NA">2013-01-01</col>
</row>
EOT;
        $district = new District();
        $district->setCode('0201')
            ->setName('Bolesławiec');

        $communityType = new CommunityType();
        $communityType->setType(1)
            ->setName('gmina miejska');

        $or->findOneBy(array(
            'code' => '0201'
        ))->shouldBeCalled()->willReturn($district);

        $or->findOneBy(array(
            'type' => 1
        ))->shouldBeCalled()->willReturn($communityType);

        $expectedCommunity = new Community();
        $expectedCommunity->setCode('0201011')
            ->setName('Bolesławiec')
            ->setType($communityType)
            ->setDistrict($district);

        $this->beConstructedWith(new \SimpleXMLElement($xml), $om);
        $this->convertToEntity()->shouldBeLike($expectedCommunity);
    }

    function it_converts_node_to_community_with_updating_existing_one(
        ObjectManager $om, ObjectRepository $or, Community $community
    ) {
        $xml = <<<EOT
<row>
    <col name="WOJ">02</col>
    <col name="POW">01</col>
    <col name="GMI">01</col>
    <col name="RODZ">1</col>
    <col name="NAZWA">Bolesławiec</col>
    <col name="NAZDOD">gmina miejska</col>
    <col name="STAN_NA">2013-01-01</col>
</row>
EOT;
        $district = new District();
        $district->setCode('0201')
            ->setName('Bolesławiec');

        $communityType = new CommunityType();
        $communityType->setType(1)
            ->setName('gmina miejska');

        $or->findOneBy(array(
            'code' => '0201'
        ))->shouldBeCalled()->willReturn($district);

        $or->findOneBy(array(
            'type' => 1
        ))->shouldBeCalled()->willReturn($communityType);

        $or->findOneBy(array(
            'code' => '0201011'
        ))->shouldBeCalled()->willReturn($community);

        $community->setName('Bolesławiec')->shouldBeCalled()->willReturn($community);
        $community->setType($communityType)->shouldBeCalled()->willReturn($community);
        $community->setDistrict($district)->shouldBeCalled()->willReturn($community);
        $community->setCode('0201011')->shouldNotBeCalled();

        $this->beConstructedWith(new \SimpleXMLElement($xml), $om);
        $this->convertToEntity()->shouldBeLike($community);
    }

    function it_throws_exception_when_cant_convert_node_to_entity(ObjectManager $om)
    {
        $this->beConstructedWith(new \SimpleXMLElement('<row></row>'), $om);

        $exception = new TerritorialDivisionNodeConverterException();
        $this->shouldThrow($exception)->during('convertToEntity', array());
    }
}
