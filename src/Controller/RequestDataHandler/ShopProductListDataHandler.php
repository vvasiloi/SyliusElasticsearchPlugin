<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * another great project.
 * You can find more information about us on https://bitbag.shop and write us
 * an email on mikolaj.krol@bitbag.pl.
 */

declare(strict_types=1);

namespace BitBag\SyliusElasticsearchPlugin\Controller\RequestDataHandler;

use BitBag\SyliusElasticsearchPlugin\Exception\TaxonNotFoundException;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Sylius\Component\Taxonomy\Repository\TaxonRepositoryInterface;

final class ShopProductListDataHandler implements DataHandlerInterface
{
    /** @var TaxonRepositoryInterface */
    private $taxonRepository;

    /** @var LocaleContextInterface */
    private $localeContext;

    /** @var string */
    private $namePropertyPrefix;

    /** @var string */
    private $taxonsProperty;

    /** @var string */
    private $optionPropertyPrefix;

    /** @var string */
    private $attributePropertyPrefix;

    public function __construct(
        TaxonRepositoryInterface $taxonRepository,
        LocaleContextInterface $localeContext,
        string $namePropertyPrefix,
        string $taxonsProperty,
        string $optionPropertyPrefix,
        string $attributePropertyPrefix
    ) {
        $this->taxonRepository = $taxonRepository;
        $this->localeContext = $localeContext;
        $this->namePropertyPrefix = $namePropertyPrefix;
        $this->taxonsProperty = $taxonsProperty;
        $this->optionPropertyPrefix = $optionPropertyPrefix;
        $this->attributePropertyPrefix = $attributePropertyPrefix;
    }

    public function retrieveData(array $requestData): array
    {
        $slug = $requestData['slug'];
        $taxon = $this->taxonRepository->findOneBySlug($slug, $this->localeContext->getLocaleCode());

        if (null === $taxon) {
            throw new TaxonNotFoundException();
        }

        $data[$this->namePropertyPrefix] = (string) $requestData[$this->namePropertyPrefix];
        $data[$this->taxonsProperty] = strtolower($taxon->getCode());
        $data['taxon'] = $taxon;
        $data = array_merge($data, $requestData['price']);

        $this->handlePrefixedProperty($requestData, $data, 'options', $this->optionPropertyPrefix);
        $this->handlePrefixedProperty($requestData, $data, 'attributes', $this->attributePropertyPrefix);

        return $data;
    }

    private function handlePrefixedProperty(
        array $requestData,
        array &$data,
        string $formName,
        string $propertyPrefix
    ): void {
        if (!isset($requestData[$formName])) {
            return;
        }

        foreach ($requestData[$formName] as $key => $value) {
            if (0 === strpos($key, $propertyPrefix)) {
                if (is_array($value)) {
                    $data[$key] = array_map(function (string $property): string {
                        return strtolower($property);
                    }, $value);
                } elseif (filter_var($value, FILTER_VALIDATE_BOOL)) {
                    $data[$key] = $value;
                }
            }
        }
    }
}
