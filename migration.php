<?php


/**
 * Class definition update migrations scenario actions
 **/
class ws_m_1607327729_perenos_opisaniya_tovara_iz_mnozhestvennogo_svoystva_v_detalnoe_opisanie extends \WS\ReduceMigrations\Scenario\ScriptScenario
{

    /**
     * Name of scenario
     **/
    static public function name()
    {
        return "Перенос описания товара из множественного свойства в детальное описание";
    }

    /**
     * Priority of scenario
     **/
    static public function priority()
    {
        return self::PRIORITY_HIGH;
    }

    /**
     * @return string hash
     */
    static public function hash()
    {
        return "23cd7b6e4801f8c20941b583a7cf24eec72e43b9";
    }

    /**
     * @return int approximately time in seconds
     */
    static public function approximatelyTime()
    {
        return 0;
    }

    /**
     * Write action by apply scenario. Use method `setData` for save need rollback data
     **/
    public function commit()
    {
        CModule::IncludeModule("iblock");
        $start = microtime(true);
        $startMemory = 0;
        $arSelect = [
            "ID",
            "IBLOCK_ID",
            "PROPERTY_TEXTS"
        ];

        $arFilter = [
            "IBLOCK_ID" => $this->getIblockId(),
            "!PROPERTY_TEXTS" => false
        ];

        $res = CIBlockElement::GetList(array(), $arFilter, false, false, $arSelect);

        fwrite(STDOUT, sprintf('найдено %d элементов', $res->SelectedRowsCount()) . "\r\n");

        $generator = $this->makeGenerator($res, 10);

        $el = new CIBlockElement;

        fwrite(STDOUT, 'старт обновления' . "\r\n");

        $cnt = 1;
        $ids = [];
        try {
            foreach ($generator as $request) {
                fwrite(STDOUT, sprintf('страница номер %d', $cnt) . "\r\n");
                $cnt++;
                while ($res = $request->Fetch()) {
                    if (!$res || !$res['ID']) {
                        continue;
                    }
                    fwrite(STDOUT, sprintf('обновляется элемент %d', $res['ID']) . "\r\n");
                    $ids[$res['ID']] = $res['ID'];

                    $el->Update(
                        $res["ID"],
                        [
                            "DETAIL_TEXT" => $res['PROPERTY_TEXTS_VALUE']['TEXT'],
                        ]
                    );

                    fwrite(STDOUT, sprintf('элемент с id:%s обновлен', $res["ID"]) . "\r\n");
                }
            }
        } catch (Exception $exception) {
            fwrite(STDOUT, $exception->getMessage() . "\r\n");
        }

        $this->setData(['IDS' => $ids]);
        $startMemory = memory_get_usage();
        $time = microtime(true) - $start;

        fwrite(STDOUT, sprintf('время выполнения скрипта:%d', $time) . "\r\n");
        fwrite(STDOUT, sprintf('потребление памяти:%d', $startMemory / (1024 * 1024) . "\r\n"));
    }

    /**
     * Write action by rollback scenario. Use method `getData` for getting commit saved data
     **/
    public function rollback()
    {
        CModule::IncludeModule("iblock");
        $start = microtime(true);
        $startMemory = 0;
        $data = $this->getData();
        $elementsId = $data['IDS'];
        $el = new CIBlockElement;

        $arLoadProductArray = array(
            "DETAIL_TEXT" => "",
        );
        try {
            foreach ($elementsId as $id) {
                $res = $el->Update($id, $arLoadProductArray);
                if ($res) {
                    fwrite(STDOUT, sprintf('элемент с id:%s обновлен', $id) . "\r\n");
                } else {
                    fwrite(STDOUT, sprintf('элемент с id:%s не обновлен по причинес:%s', $id, $el->LAST_ERROR) . "\r\n");
                }
            }
        } catch (Exception $exception) {
            fwrite(STDOUT, $exception->getMessage() . "\r\n");

        }

        $startMemory = memory_get_usage();
        $time = microtime(true) - $start;

        fwrite(STDOUT, sprintf('время выполнения скрипта:%d', $time) . "\r\n");
        fwrite(STDOUT, sprintf('потребление памяти:%d', $startMemory / (1024 * 1024) . "\r\n"));
    }

    private function makeGenerator($selectRequest, $chunkSize)
    {
        $pageNumber = 1;
        $totalPages = ceil($selectRequest->SelectedRowsCount() / $chunkSize);
        while ($pageNumber <= $totalPages) {
            $selectRequest->NavStart($chunkSize, false, $pageNumber);
            yield $selectRequest;
            $pageNumber++;
        }
    }

    private function getIblockId()
    {
        $dbIblock = CIBlock::GetList([], ['NAME' => 'Основной каталог товаров'], false)->Fetch();
        return $dbIblock['ID'];
    }
}

