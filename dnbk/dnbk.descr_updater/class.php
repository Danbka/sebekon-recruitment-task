<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Iblock\ORM\ElementV2Table;
use Bitrix\Main\Web\HttpClient;

class DescriptionUpdaterComponent extends \CBitrixComponent
{
	/** описания коллекций */
	private $arDescriptions = [];
	
	/**
	 * @return CUser
	 */
	private function getUser()
	{
		$user = null;
		
		if ($user === null) {
			$user = $GLOBALS['USER'];
		}
		
		return $user;
	}
	
	/**
	 * @return bool
	 */
	private function isAdmin()
	{
		$user = $this->getUser();
		
		return $user->IsAdmin();
	}
	
	/**
	 * получить описание коллекций
	 */
	private function getCollectionsDescription($arCollections)
	{
		$arDescriptions = [];
		
		foreach ($arCollections as $collectionName) {
			try {
				$arDescriptions[$collectionName] = $this->getDescriptionByCollectionName($collectionName);
			} catch (Exception $exception) {
				AddMessage2Log("Cannot get description for collection ".$collectionName.": ".$exception->getMessage());
				continue;
			}
		}
		
		return $arDescriptions;
	}
	
	/**
	 * получить описание коллекции
	 *
	 * @throws Exception
	 */
	private function getDescriptionByCollectionName($collectionName)
	{
		$client = new HttpClient();
		
		$client->get($this->arParams["API_URL"] . "?collection=" . $collectionName);
		
		if ($client->getStatus() !== 200) {
			throw new Exception("Request not performed", $client->getStatus());
		}
		
		return $client->getResult();
	}
	
	/**
	 * обновить описание продуктов
	 *
	 * @throws Exception
	 */
	private function updateProductsDescription()
    {
	    $rsProducts = CIBlockElement::GetList(
		    [],
		    [
			    "IBLOCK_ID" => $this->arParams["IBLOCK_ID"],
		    ],
		    false,
		    false,
		    [
			    'ID',
			    'PROPERTY_COLLECTION'
		    ]
	    );
	    
	    while ($arProduct = $rsProducts->Fetch()) {
	    	$collectionName = $arProduct["PROPERTY_COLLECTION_VALUE"];
		
		    if (isset($this->arDescriptions[$collectionName])) {
			    $result = ElementV2Table::update($arProduct["ID"], [
				    "PREVIEW_TEXT" => $this->arDescriptions[$collectionName],
				    "PREVIEW_TEXT_TYPE" => "text",
			    ]);
			
			    if (!$result->isSuccess()) {
				    // ошибки можно залогировать,
				    // вывести администратору
				    // или прервать процесс обновления
			    }
		    }
	    }
    }
	
	/**
	 * получить названия всех коллекций
	 */
	private function getAllCollections()
	{
		$arCollections = [];
		
		$rsCollections = CIBlockElement::GetList(
			[
				"PROPERTY_COLLECTION" => "ASC",
			],
			[
				"IBLOCK_ID" => $this->arParams["IBLOCK_ID"],
			],
			[
				"PROPERTY_COLLECTION",
			]
		);
		
		while ($arCollection = $rsCollections->Fetch()) {
			$arCollections[] = $arCollection["PROPERTY_COLLECTION_VALUE"];
		}
		
		return $arCollections;
	}
	
	public function executeComponent()
	{
		if (!$this->isAdmin()) {
			ShowError(GetMessage("ACCESS_DENIED"));
			return false;
		}
		
		try {
			$this->arResult["UPDATED"] = false;
			
			if ($this->request->isPost()) {
				
				$this->arDescriptions = $this->getCollectionsDescription(
					$this->getAllCollections()
				);
				
				$this->updateProductsDescription();
				
				$this->arResult["UPDATED"] = true;
			}
		} catch (Exception $exception) {
			$this->arResult["ERROR_MESSAGE"] = $exception->getMessage();
		}
		
		$this->includeComponentTemplate();
	}
}
