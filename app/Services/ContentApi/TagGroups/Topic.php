<?php

namespace App\Services\ContentApi\TagGroups;

class Topic extends TagGroup
{
    const TAG_GROUP_ID = 1000;

    const TAG_ID_ENFORCEMENT_AND_LITIGATION = 2390;
    const TAG_ID_TRADEMARK_LAW = 2393;
    const TAG_ID_ANTI_COUNTERFEITING = 2492;
    const TAG_ID_IP_OFFICES = 2509;
    const TAG_ID_GOVERNMENT_POLICY = 2483;
    const TAG_ID_PORTFOLIO_MANAGEMENT = 2391;
    const TAG_ID_BRAND_MANAGEMENT = 2482;
    const TAG_ID_LAW_FIRMS = 2499;
    const TAG_ID_ONLINE = 2495;
    const TAG_ID_COPYRIGHT_LAW = 2600;
    const TAG_ID_LAW_AND_POLICY = 2597;
    const TAG_ID_DESIGNS = 2605;
    const TAG_ID_FINANCE = 2749;
    const TAG_ID_LITIGATION = 2737;
    const TAG_ID_MARKET_DEVELOPMENT = 2718;
    const TAG_ID_FRAND_SEPS = 2717;
    const TAG_ID_NON_PRACTICING_ENTITIES = 2741;
    const TAG_ID_PATENT_POOLS = 2750;
    const TAG_ID_PATENTS_LAW = 2598;
    const TAG_ID_STRATEGY = 2594;
    const TAG_ID_TECHNOLOGY_LICENCING = 2719;
    const TAG_ID_TRADE_SECRETS = 2595;
    const TAG_ID_VALUATION = 2739;

    public static $topics = [
        self::TAG_ID_ENFORCEMENT_AND_LITIGATION => 'Enforcement and Litigation',
        self::TAG_ID_TRADEMARK_LAW => 'Trademark law',
        self::TAG_ID_ANTI_COUNTERFEITING => 'Anti-Counterfeiting',
        self::TAG_ID_IP_OFFICES => 'IP Offices',
        self::TAG_ID_GOVERNMENT_POLICY => 'Government/Policy',
        self::TAG_ID_PORTFOLIO_MANAGEMENT => 'Portfolio Management',
        self::TAG_ID_BRAND_MANAGEMENT => 'Brand management',
        self::TAG_ID_LAW_FIRMS => 'Law Firms',
        self::TAG_ID_ONLINE => 'Online',
        self::TAG_ID_COPYRIGHT_LAW => 'Copyright Law',
        self::TAG_ID_LAW_AND_POLICY => 'Law & Policy',
        self::TAG_ID_DESIGNS => 'Designs',
        self::TAG_ID_FINANCE => 'Finance',
        self::TAG_ID_LITIGATION => 'Litigation',
        self::TAG_ID_MARKET_DEVELOPMENT => 'Market Developments',
        self::TAG_ID_FRAND_SEPS => 'Frand/SEPS',
        self::TAG_ID_NON_PRACTICING_ENTITIES => 'Non-Practising Entities',
        self::TAG_ID_PATENT_POOLS => 'Patent Pools',
        self::TAG_ID_PATENTS_LAW => 'Patents Law',
        self::TAG_ID_STRATEGY => 'Strategy',
        self::TAG_ID_TECHNOLOGY_LICENCING => 'Technology Licensing',
        self::TAG_ID_TRADE_SECRETS => 'Trade Secrets',
        self::TAG_ID_VALUATION => 'Valuation',
    ];
}
