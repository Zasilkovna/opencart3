<?php

namespace Packetery\DAL\Repository;

class PacketaRepository {

    /**
     * TODO: Není vyřešena situace, pokud bychom potřebovali zavřít konkrétního vendora Packety.
     * Ať už v konkrétní zemi, nebo komplet jeden typ výdejního místa. Pokud odmažeme zemi nebo celou
     * skupinu (group), vyhodíme vyjímku při sestavení již neexistujího vendora. Toto musíme nějak ošetřit
     * pro případ, že by Zásilkovna něco zrušila. U dopravců by se to stát nemělo, protože dopravce
     * označujeme jako smazané, ale záznamy v DB si necháváme. Zde je vyjímka v pořádku.
    */
    const PACKETA_VENDORS = [
        [
            'group' => 'zpoint',
            'name' => 'vendor_add_zpoint',
            'countries' => ['cz', 'sk', 'hu', 'ro',]
        ],
        [
            'group' => 'zbox',
            'name' => 'vendor_add_zbox',
            'countries' => ['cz', 'sk', 'hu', 'ro',]
        ],
        [
            'group' => 'alzabox',
            'name' => 'vendor_add_alzabox',
            'countries' => ['cz',]
        ],
    ];

    /**
     * @param string $packetaId
     * @return array|null
     */
    public function byId($packetaId) {
        $packetaVendors = $this->fetchAll();

        return isset($packetaVendors[$packetaId]) ? $packetaVendors[$packetaId] : null;
    }

    /**
     * @param string $country
     * @return array
     */
    public function byCountry($country) {
        $vendors = $this->fetchAll();

        return array_filter($vendors, function ($item) use ($country) {
            return isset($item['country']) && $item['country'] === $country;
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * @return array
     */
    public function fetchAll() {
        return array_reduce(self::PACKETA_VENDORS, function ($vendor, $item) {
            $newItems = array_map(function ($country) use ($item) {
                $id = $country . $item['group'];

                return [
                    $id => [
                        'id' => $id,
                        'name' => $item['name'],
                        'group' => $item['group'],
                        'country' => $country,
                    ]
                ];
            }, $item['countries']);

            return array_merge($vendor, ...$newItems);
        }, []);
    }
}
