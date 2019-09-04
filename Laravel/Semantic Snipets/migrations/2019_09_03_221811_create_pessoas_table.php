<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

//php artisan make:migration PessoasTable --create=pessoas
class CreatePessoasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cards', function (Blueprint $table) {
            $table->increments('card_id');
            
            $table->boolean('banned'); //BOOLEAN equivalent column. Equivalent to tiny_int(1) in the data base
            $table->integer('number_of_copies'); //INTEGER equivalent column.
            $table->decimal('preco', 9, 2); //DECIMAL equivalent column with a precision (total digits) and scale (decimal digits).

            $table->char('classe', 2); //CHAR equivalent column with an optional length.
            $table->string('name', 255);//VARCHAR equivalent column with a optional length.
            $table->string('image_name', 255);//VARCHAR equivalent column with a optional length.
            
            $table->date('creation'); //DATE equivalent column. ‘YYYY-MM-DD HH:MM:SS’ format. The supported range is ‘1000-01-01 00:00:00’ to ‘9999-12-31 23:59:59’.”
            $table->dateTimeTz('released_in_japan'); //DATETIME (with timezone) equivalent column.
            $table->timestampTz('added_on'); //TIMESTAMP (with timezone) equivalent column. TIMESTAMP has a range of ‘1970-01-01 00:00:01’ UTC to ‘2038-01-19 03:14:07’ UTC.”
            $table->softDeletes();//Adds a nullable deleted_at TIMESTAMP equivalent column for soft deletes. Add a 'Tz' at the end to add time zone info
            $table->timestamps(); //Adds nullable created_at and  updated_at TIMESTAMP equivalent columns.
            $table->year('official_release'); //YEAR equivalent column.

            $table->enum('sabor', ['morango', 'chocolate', 'côco', 'napolitano']); //ENUM equivalent column. While ENUM can only hold one value, SET can hold a collection of them
            $table->set('pokemon_types', ['Water', 'Fire', 'Electric', 'Grass', 'Rock', 'Dragon', 'Flying']); //SET equivalent column. While ENUM can only hold one value, SET can hold a collection of them            
            
            $table->integer('valor1')->nullable(); //Allows (by default) NULL values to be inserted into the column
            $table->integer('valor2')->unsigned(); //Allows (by default) NULL values to be inserted into the column
            //$table->integer('total')->virtualAs($expression); Create a virtual generated column (MySQL)

            //To rename an existing database table
            //Schema::rename($from, $to);

            //Changes a column
            //$table->string('name', 50)->nullable()->change();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pessoas');
    }
}
