<?php

class Health_Check_Tools_Test extends WP_UnitTestCase {

	private $test_files_integrity;
	private $core_checksums;

	public function setUp() {
		parent::setUp();

		$this->test_files_integrity = new Health_Check_Files_Integrity();

		$this->core_checksums = $this->test_files_integrity->call_checksum_api();
	}

	public function testFilesIntegrityUntampered() {
		$files = $this->test_files_integrity->parse_checksum_results( $this->core_checksums );

		$this->assertEmpty( $files );
	}

	public function testFilesIntegrityMissingFiles() {
		$original_file = trailingslashit( ABSPATH ) . 'xmlrpc.php';
		$renamed_file  = trailingslashit( ABSPATH ) . 'xmlrpc-moved.php';

		rename( $original_file, $renamed_file );

		$files = $this->test_files_integrity->parse_checksum_results( $this->core_checksums );

		$this->assertEquals(
			array(
				array(
					'xmlrpc.php',
					'File not found',
				),
			),
			$files
		);

		rename( $renamed_file, $original_file );
	}

	public function testFilesIntegrityModifiedFiles() {
		$filename = trailingslashit( ABSPATH ) . 'xmlrpc.php';

		file_put_contents( $filename, PHP_EOL . '// Modified file content.' . PHP_EOL, FILE_APPEND );

		$files = $this->test_files_integrity->parse_checksum_results( $this->core_checksums );

		$this->assertEquals(
			array(
				array(
					'xmlrpc.php',
					'Content changed <a href="#health-check-diff" data-file="xmlrpc.php">(View Diff)</a>',
				),
			),
			$files
		);
	}
}
