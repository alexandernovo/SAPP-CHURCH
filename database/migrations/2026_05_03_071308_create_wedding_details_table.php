<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('wedding_details', function (Blueprint $table) {
            $table->bigIncrements('weddingDetailsId');

            $table->unsignedBigInteger('weddingId')->nullable()->index();

            $table->text('groomFullName')->nullable();
            $table->unsignedTinyInteger('groomAge')->nullable();
            $table->date('groomDateOfBirth')->nullable();
            $table->text('groomPlaceOfBirth')->nullable();
            $table->text('groomPresentAddress')->nullable();
            $table->text('groomFather')->nullable();
            $table->text('groomMotherMaiden')->nullable();
            $table->text('groomReligion')->nullable();
            $table->date('groomBaptismDate')->nullable();
            $table->text('groomBaptismPlace')->nullable();
            $table->text('groomConfirmationDate')->nullable();
            $table->text('groomContact')->nullable();
            $table->text('groomSignature')->nullable();


            $table->text('brideFullName')->nullable();
            $table->unsignedTinyInteger('brideAge')->nullable();
            $table->date('brideDateOfBirth')->nullable();
            $table->text('bridePlaceOfBirth')->nullable();
            $table->text('bridePresentAddress')->nullable();
            $table->text('brideFather')->nullable();
            $table->text('brideMotherMaiden')->nullable();
            $table->text('brideReligion')->nullable();
            $table->date('brideBaptismDate')->nullable();
            $table->text('brideBaptismPlace')->nullable();
            $table->text('brideConfirmationDate')->nullable();
            $table->text('brideContact')->nullable();
            $table->text('brideSignature')->nullable();

            $table->date('civilMarriageDate')->nullable();
            $table->text('civilMarriagePlace')->nullable();
            $table->date('prenuptialInvestigationDate')->nullable();
            $table->date('churchWeddingDate')->nullable();
            $table->text('churchWeddingPlace')->nullable();
            $table->text('officiatingPriest')->nullable();
            $table->text('sponsorsLine1')->nullable();
            $table->text('sponsorsLine2')->nullable();
            $table->text('sponsorsLine3')->nullable();


            $table->boolean('docBaptismalCertificate')->default(false);
            $table->boolean('docConfirmationCertificate')->default(false);
            $table->boolean('docCivilMarriage')->default(false);
            $table->boolean('docPrenuptialInterrogation')->default(false);
            $table->boolean('docPreCana')->default(false);
            $table->text('docPreCanaRemarks')->nullable();
            $table->boolean('docWeddingFees')->default(false);
            $table->text('docWeddingFeesRemarks')->nullable();
            $table->boolean('docMarriageCertificate')->default(false);
            $table->text('docMarriageCertificateRemarks')->nullable();
            $table->boolean('docPresider')->default(false);
            $table->text('docPresiderRemarks')->nullable();

       
            $table->text('parishSecretaryName')->nullable();
            $table->date('dateOfApplication')->nullable();
            $table->text('arNumber')->nullable();

        
            $table->json('precanaSchedule')->nullable();

       
            $table->json('marriageSponsors')->nullable();

        
            $table->text('approvalBpcChairman')->nullable();
            $table->text('approvalParishFiscalSecretary')->nullable();
            $table->text('approvalMinister')->nullable();

            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('wedding_details');
    }
};
