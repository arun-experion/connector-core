<?php

use Connector\Integrations\Response;
use Connector\Integrations\Spreadsheet\AbstractSpreadsheet;
use Connector\Mapping;
use Connector\Mapping\Item;
use Connector\Record\RecordLocator;
use Connector\Record\Recordset;
use PHPUnit\Framework\TestCase;

/**
 * @uses Connector\Integrations\Response
 * @uses Connector\Mapping
 * @uses Connector\Mapping\Item
 * @uses Connector\Record\RecordLocator
 * @uses Connector\Record\RecordKey
 * @uses Connector\Integrations\AbstractIntegration
 * @covers Connector\Integrations\Spreadsheet\SpreadsheetRecordLocator
 * @covers Connector\Integrations\Spreadsheet\AbstractSpreadsheet
 */
final class AbstractSpreadsheetTest extends TestCase
{

    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testExtract(): void
    {
        $recordLocator = new RecordLocator(["recordType" => "__fake__spreadsheet__id__", "range" => "Sheet1!A2:D5" ]);
        $mapping       = new Mapping("1","2","3","4");

        $stub = $this->getMockForAbstractClass(AbstractSpreadsheet::class);

        $stub->expects($this->once())
            ->method('getRows')
            ->with("__fake__spreadsheet__id__", "Sheet1", "Sheet1!A2:D5")
            ->will($this->returnValue(new Recordset()));

        $response = $stub->extract($recordLocator, $mapping, null);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertInstanceOf(Recordset::class, $response->recordset);
        $this->assertCount(0, $response->recordset->records);

    }

    /**
     * Checks that the implementable methods of the abstract class are called with the right parameters when loading data
     * @return void
     * @throws \Connector\Exceptions\EmptyRecordException
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testBatchLoadWithDefaultSheetName(): void
    {
        $recordLocator = new RecordLocator(["recordType" => "__fake__spreadsheet__id__" ]);
        $mapping       = new Mapping();
        $mapping[]     = new Item("StudentName", "Jane Doe",  "Student Name");
        $mapping[]     = new Item("Semester", "Fall 2023",  "Semester");
        $mapping[]     = new Item("Course", "English 101",  "Course");
        $mapping[]     = new Item("Grade", "A",  "Grade");

        $stub = $this->getMockForAbstractClass(AbstractSpreadsheet::class);
        $stub->expects($this->once())
            ->method('createOrFindSpreadsheet')
            ->will($this->returnValue("fakeFileResource"));

        $stub->expects($this->once())
            ->method('createOrFindSheet')
            ->with("fakeFileResource", "Sheet1", [ "Student Name", "Semester", "Course", "Grade"])
            ->will($this->returnValue("fakeSheetResource"));

        $stub->expects($this->once())
            ->method('insertRows')
            ->with("fakeFileResource", "fakeSheetResource", [[ "Jane Doe", "Fall 2023", "English 101", "A"]]);


        $stub->begin();
        $response = $stub->load($recordLocator, $mapping, null);
        $this->assertInstanceOf(Response::class, $response);

        $stub->end();
    }

    /**
     * An arbitrary sheet name can be provided in the record locator, with the 'sheet' attribute.
     * @return void
     * @throws \Connector\Exceptions\EmptyRecordException
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testBatchLoadWithCustomSheetName(): void
    {
        $recordLocator = new RecordLocator(["recordType" => "__fake__spreadsheet__id__", "sheet" => "ABC" ]);
        $mapping       = new Mapping();
        $mapping[]     = new Item("StudentName", "Jane Doe",  "Student Name");

        $stub = $this->getMockForAbstractClass(AbstractSpreadsheet::class);

        $stub->method('createOrFindSpreadsheet')
            ->will($this->returnValue("fakeFileResource"));

        $stub->expects($this->once())
            ->method('createOrFindSheet')
            ->with("fakeFileResource", "ABC", [ "Student Name"])
            ->will($this->returnValue("fakeSheetResource"));

        $stub->method('insertRows')
            ->with("fakeFileResource", "fakeSheetResource", [[ "Jane Doe"]]);

        $stub->begin();
        $stub->load($recordLocator, $mapping, null);
        $stub->end();
    }


    /**
     * Previously batched data is cleared when running a new transaction
     * @return void
     * @throws \Connector\Exceptions\EmptyRecordException
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testBatchReset(): void
    {
        $recordLocator = new RecordLocator(["recordType" => "__fake__spreadsheet__id__", "sheet" => "ABC" ]);
        $mapping       = new Mapping();
        $mapping[]     = new Item("StudentName", "Jane Doe",  "Student Name");

        $stub = $this->getMockForAbstractClass(AbstractSpreadsheet::class);
        $stub->method('createOrFindSpreadsheet')
            ->will($this->returnValue("fakeFileResource"));

        $stub->expects($this->once())
            ->method('createOrFindSheet')
            ->with("fakeFileResource", "ABC", [ "Student Name"])
            ->will($this->returnValue("fakeSheetResource"));

        $stub->method('insertRows')
            ->with("fakeFileResource", "fakeSheetResource", [[ "Jane Doe"]]);

        $stub->begin();
        $stub->load($recordLocator, $mapping, null);
        $stub->end();

        $stub->begin();
        $stub->expects($this->never())->method('createOrFindSpreadsheet');
        $stub->expects($this->never())->method('createOrFindSheet');
        $stub->expects($this->never())->method('insertRows');
        $stub->end();
    }

}
