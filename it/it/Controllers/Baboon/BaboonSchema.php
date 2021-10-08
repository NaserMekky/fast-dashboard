<?php

namespace Phpanonymous\It\Controllers\Baboon;

use App\Http\Controllers\Controller;

class BaboonSchema extends Controller
{

	public static function convention_name($string)
	{
		$conv = strtolower(ltrim(preg_replace('/(?<!\ )[A-Z]/', '_$0', $string), '_'));
		if (!in_array(substr($conv, -1), ['s'])) {
			if (substr($conv, -1) == 'y') {
				$conv = substr($conv, 0, -1) . 'ies';
			} else {
				$conv = $conv . 's';
			}
		}
		return $conv;
	}

	public static function autoconvSchemaTableName($conv)
	{
		if (!in_array(substr($conv, -1), ['s'])) {
			if (substr($conv, -1) == 'y') {
				$conv = substr($conv, 0, -1) . 'ies';
			} else {
				$conv = $conv . 's';
			}
		}
		return $conv;
	}

	public static function migrate($r)
	{
		$migrate = '
<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

// Auto Schema  By Baboon Script
// Baboon Maker has been Created And Developed By ' . it_version_message() . '
// Copyright Reserved  ' . it_version_message() . '
class Create{ClassName}Table extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create(\'{TBLNAME}\', function (Blueprint $table) {
			$table->bigIncrements(\'id\');'."\n";

		$migrate .= self::get_cols($r);
		if (request()->has('enable_soft_delete')) {
			$migrate .= "\t\t\t\t\t".'$table->softDeletes();' . "\n\r";
		}
		$migrate .= "\t\t\t\t\t".'$table->timestamps();
        });
		}

		/**
		 * Reverse the migrations.
		 *
		 * @return void
		 */
		public function down()
		{
			Schema::dropIfExists(\'{TBLNAME}\');
		}
		}';

		$migrate = str_replace('{ClassName}', self::autoconvSchemaTableName($r->input('model_name')), $migrate);
		$migrate = str_replace('{TBLNAME}', self::convention_name($r->input('model_name')), $migrate);
		return $migrate;
	}

	public static function get_cols($r)
	{
		$cols = '';
		$i = 0;

		if ($r->has('has_user_id')) {
			$cols .= "\t\t\t\t\t".'$table->bigInteger(\'admin_id\')->unsigned()->nullable();' . "\n";
			$cols .= "\t\t\t\t\t".'$table->foreign(\'admin_id\')->references(\'id\')->on(\'admins\')->onDelete(\'cascade\');' . "\n";
			/*
				$table->bigInteger('user_id')->unsigned()->nullable();
				$table->foreign('user_id')->references('id')->on('users')->o‌​nDelete('cascade');
			*/
		}

		/*if ($r->has('schema_name')) {
			$i           = 0;
			$schema_null = $r->input('schema_null');
			foreach ($r->input('schema_name') as $schema_name) {
			if (!empty($schema_null[$i])) {
			$cols .= "\t\t\t\t\t".'$table->bigInteger(\''.$schema_name.'\')->nullabel();'."\n";

			} else {
			$cols .= "\t\t\t\t\t".'$table->bigInteger(\''.$schema_name.'\');'."\n";
			}
			$i++;
			}
		*/
		$i2 = 0;

		foreach ($r->input('col_name_convention') as $conv) {
			if ($r->has('forginkeyto' . $i2)) {
				$cols .= self::forgin_key($conv, $i2, $r);
			} elseif (preg_match('/(\d+)\+(\d+)|,/i', $conv)) {
				$cols .= self::enum($conv, $i2, $r);
			} elseif (preg_match('/#/i', $conv)) {
				$pre_conv = explode('#', $conv);
				if (!preg_match('/' . $pre_conv[0] . '/i', $cols)) {
					$cols .= self::check_radio($conv, $i2, $r);
				}
			} else {
				$cols .= self::str_num($conv, $i2, $r);

			}
			$i2++;
		}

		return $cols;
	}

	public static function forgin_key($name, $i, $r)
	{
		if (preg_match('/|/', $name)) {
			$name = explode('|', $name)[0];
		}
		$cols = '';

		$references = $r->input('references' . $i);
		$tblname = $r->input('forgin_table_name' . $i);
		if ($r->has('schema_nullable' . $i)) {
			$cols .= "\t\t\t\t\t".'$table->bigInteger(\'' . $name . '\')->unsigned()->nullable();' . "\r\n";
		} else {
			$cols .= "\t\t\t\t\t".'$table->bigInteger(\'' . $name . '\')->unsigned();' . "\r\n";
		}

		if ($r->has('schema_onDelete' . $i) && $r->has('schema_onUpdate' . $i)) {
			$cols .= "\t\t\t\t\t".'$table->foreign(\'' . $name . '\')->references(\'' . $references . '\')->on(\'' . $tblname . '\')->onDelete(\'cascade\')->onUpdate(\'cascade\');' . "\r\n";
		} elseif ($r->has('schema_onDelete' . $i)) {
			$cols .= "\t\t\t\t\t".'$table->foreign(\'' . $name . '\')->references(\'' . $references . '\')->on(\'' . $tblname . '\')->onDelete(\'cascade\');' . "\r\n";
		} elseif ($r->has('schema_onUpdate' . $i)) {
			$cols .= "\t\t\t\t\t".'$table->foreign(\'' . $name . '\')->references(\'' . $references . '\')->on(\'' . $tblname . '\')->onUpdate(\'cascade\');' . "\r\n";
		} else {
			$cols .= "\t\t\t\t\t".'$table->foreign(\'' . $name . '\')->references(\'' . $references . '\')->on(\'' . $tblname . '\');' . "\r\n";
		}

		return $cols;
	}

	public static function check_radio($name, $i, $r)
	{
		$col = '';
		$name = explode('#', $name);
		if ($r->input('col_name_null' . $i) == 'has') {
			if ($r->has('numeric' . $i) and $r->has('numeric' . $i) == 1) {
				$col .= "\t\t\t\t\t".'$table->bigInteger(\'' . $name[0] . '\'';
			} elseif (!$r->has('numeric' . $i)) {
				if ($r->input('col_type')[$i] == 'textarea') {
					$col .= "\t\t\t\t\t".'$table->longtext(\'' . $name[0] . '\'';
				} elseif ($r->input('col_type')[$i] == 'textarea_ckeditor') {
					$col .= "\t\t\t\t\t".'$table->longtext(\'' . $name[0] . '\'';
				} else {
					$col .= "\t\t\t\t\t".'$table->string(\'' . $name[0] . '\'';
				}
			}

			if ($r->has('required' . $i)) {
				$col .= ');' . "\n";
			} else {
				$col .= ')->nullable();' . "\n";
			}
		} else {

			$col .= "\t\t\t\t\t".'$table->string(\'' . $name[0] . '\'';
			$col .= ')->nullable();' . "\n";
		}

		return $col;
	}

	public static function str_num($name, $i, $r)
	{
		$col = '';
		if ($r->input('col_name_null' . $i) == 'has') {
			if ($r->has('numeric' . $i) and $r->has('numeric' . $i) == 1) {
				$col .= "\t\t\t\t\t".'$table->bigInteger(\'' . $name . '\'';
			} elseif (!$r->has('numeric' . $i)) {
				if (!empty($r->input('col_type')[$i]) and $r->input('col_type')[$i] == 'textarea') {
					$col .= "\t\t\t\t\t".'$table->longtext(\'' . $name . '\'';
				} elseif (!empty($r->input('col_type')[$i]) and $r->input('col_type')[$i] == 'textarea_ckeditor') {
					$col .= "\t\t\t\t\t".'$table->longtext(\'' . $name . '\'';
				} elseif ($r->input('col_type')[$i] == 'date_time') {
					$col .= "\t\t\t\t\t".'$table->dateTime(\'' . $name . '\'';
				} elseif ($r->input('col_type')[$i] == 'date') {
					$col .= "\t\t\t\t\t".'$table->date(\'' . $name . '\'';
				} elseif ($r->input('col_type')[$i] == 'time') {
					$col .= "\t\t\t\t\t".'$table->time(\'' . $name . '\'';
				} elseif ($r->input('col_type')[$i] == 'timestamp') {
					$col .= "\t\t\t\t\t".'$table->timestamp(\'' . $name . '\'';
				} elseif ($r->has('date' . $i)) {
					$col .= "\t\t\t\t\t".'$table->date(\'' . $name . '\'';
				} elseif ($r->input('col_type')[$i] == 'dropzone') {
					$col .= "\t\t\t\t\t".'$table->string(\'' . $name . '\'';
				}elseif ($r->input('col_type')[$i] != 'dropzone') {
					$col .= "\t\t\t\t\t".'$table->string(\'' . $name . '\'';
				}

				// // Edit By Naser 
				// if ($r->has('required' . $i)) {
				// 	$col .= '); // Naser' . "\n";
				// } else {
				// 	$col .= ')->nullable(); //Naser' . "\n";
				// }
				// // Edit By Naser 
			}
			// comments By Naser 
			// if ($r->input('col_type')[$i] != 'dropzone') {
			// 	if ($r->has('required' . $i)) {
			// 		$col .= '); // Naser' . "\n";
			// 	} else {
			// 		$col .= ')->nullable(); //Naser' . "\n";
			// 	}
			// }
			// comments By Naser 

			
		} else {

			if (!empty($r->input('col_type')[$i]) and $r->input('col_type')[$i] == 'textarea') {
				$col .= "\t\t\t\t\t".'$table->longtext(\'' . $name . '\'';
			} elseif (!empty($r->input('col_type')[$i]) and $r->input('col_type')[$i] == 'textarea_ckeditor') {
				$col .= "\t\t\t\t\t".'$table->longtext(\'' . $name . '\'';
			} elseif ($r->input('col_type')[$i] == 'date_time') {
				$col .= "\t\t\t\t\t".'$table->dateTime(\'' . $name . '\'';
			} elseif ($r->input('col_type')[$i] == 'date') {
				$col .= "\t\t\t\t\t".'$table->date(\'' . $name . '\'';
			} elseif ($r->input('col_type')[$i] == 'time') {
				$col .= "\t\t\t\t\t".'$table->time(\'' . $name . '\'';
			} elseif ($r->input('col_type')[$i] == 'timestamp') {
				$col .= "\t\t\t\t\t".'$table->timestamp(\'' . $name . '\'';
			} elseif ($r->has('date' . $i)) {
				$col .= "\t\t\t\t\t".'$table->date(\'' . $name . '\'';
			} elseif ($r->input('col_type')[$i] == 'dropzone') {
				$col .= "\t\t\t\t\t".'$table->string(\'' . $name . '\'';
			}elseif ($r->input('col_type')[$i] != 'dropzone') {
				$col .= "\t\t\t\t\t".'$table->string(\'' . $name . '\'';
			}

			// comments By Naser
				// if ($r->input('col_type')[$i] != 'dropzone') {
				// 	$col .= ')->nullable(); //Naser 2' . "\n";
				// }
			// comments By Naser
			
			// Edit By Naser
			//$col .= ')->nullable(); // Naser Null' . "\n";
			// Edit By Naser
		}
			// Edit By Naser 
			if ($r->has('required' . $i)) {
				$col .= '); // Naser' . "\n";
			} else {
				$col .= ')->nullable(); //Naser' . "\n";
			}
			// Edit By Naser 
		return $col;
	}

	public static function enum($name, $i, $r)
	{
		$pre_name = explode('|', $name);

		if ($r->input('forginkeyto' . $i) == 'has' || preg_match('/App/i', $pre_name[1])) {
			return self::forgin_key($pre_name[0], $i, $r);
		} else {
			$pre_name2 = explode('/', $pre_name[1]);
			$cols = "\t\t\t\t\t".'$table->enum(\'' . $pre_name[0] . '\',[';

			$listenum = '';
			foreach ($pre_name2 as $v) {

				$v2 = explode(',', $v);
				$listenum .= '\'' . $v2[0] . '\',';
			}

			$cols .= rtrim($listenum, ',');
			if ($r->input('col_name_null' . $i) == 'has') {
				if ($r->input('required' . $i) == 1) {
					$cols .= ']);' . "\n";
				} else {
					$cols .= '])->nullable();' . "\n";
				}
			} else {
				$cols .= '])->nullable();' . "\n";
			}
			return $cols;
		}
	}
}
