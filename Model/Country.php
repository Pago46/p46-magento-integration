<?php
namespace Pago46\Cashin\Model;

class Country implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'ARG', 'label' => __('Argentina')], 
            ['value' => 'CHL', 'label' => __('Chile')],
            ['value' => 'MEX', 'label' => __('México')],
            ['value' => 'ECU', 'label' => __('Ecuador')],
            ['value' => 'URY', 'label' => __('Uruguay')],
            ['value' => 'PER', 'label' => __('Perú')]
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
                'ARG' => __('Argentina'), 
                'CHL' => __('Chile'),
                'MEX' => __('México'),
                'ECU' => __('Ecuador'),
                'URY' => __('Uruguay'),
                'PER' => __('Perú'),                
            ];
    }
}
