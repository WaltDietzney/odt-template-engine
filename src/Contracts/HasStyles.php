<?php

namespace OdtTemplateEngine\Contracts;

/**
 * Interface for elements that support styles.
 * 
 * This interface should be implemented by classes that manage styles for text and paragraphs,
 * allowing them to register their styles and provide the necessary style definitions.
 */
interface HasStyles
{
    /**
     * Registers all styles that are required by the element.
     * 
     * This method should ensure that all styles, both text and paragraph styles, are registered
     * in the style manager (e.g., StyleMapper), so that they can be applied when generating the document.
     * 
     * @return void
     */
    public function registerStyles(): void;

    /**
     * Returns the style definitions that are required by the element.
     * 
     * This method should return an array of style definitions that will be used in the ODT document,
     * including text and paragraph styles, as well as any additional styles the element might use.
     * 
     * @return array Array of style definitions.
     */
    public function getStyleDefinitions(): array;


}

