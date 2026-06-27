<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Article;

class ArticlesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'law_id' => 1,
                'title_id' => 1,
                'article_number' => 1,
                'article_title' => 'Artículo 1. Objeto de la Ley',
                'article_content' => 'La presente ley tiene por objeto establecer el marco normativo para efectivizar la contratación oportuna de bienes, servicios y obras, así como regular, en el marco del Sistema Nacional de Abastecimiento, la participación de los actores involucrados en el proceso de contratación pública.',
            ],
            [
                'law_id' => 1,
                'title_id' => 1,
                'article_number' => 2,
                'article_title' => 'Artículo 2. Finalidad de la Ley',
                'article_content' => 'La presente ley tiene como finalidad maximizar el uso de recursos públicos en las contrataciones de bienes, servicios y obras por parte del Estado, en términos de eficacia, eficiencia y economía, de tal manera que dichas contrataciones permitan el cumplimiento oportuno de los fines públicos y mejoren las condiciones de vida de los ciudadanos.',
            ],
            [
                'law_id' => 1,
                'title_id' => 1,
                'article_number' => 3,
                'article_title' => 'Artículo 3. Ámbito de aplicación de la Ley',
                'article_content' => '3.1. La presente ley es aplicable para la contratación de bienes, servicios y obras, siempre que las entidades contratantes asuman el pago con fondos públicos. [cite_start]Los contratos menores se rigen por esta ley. [cite: 400, 401, 402]\n3.2. [cite_start]Se encuentran comprendidos dentro de los alcances de la ley, bajo el término genérico de entidad contratante: [cite: 403][cite_start]\na) El Poder Legislativo, de conformidad con el artículo 94 de la Constitución Política del Perú y el Reglamento del Congreso de la República, el Poder Judicial y los organismos constitucionalmente autónomos. [cite: 404][cite_start]\nb) Los ministerios, sus organismos públicos, programas y proyectos especiales. [cite: 405][cite_start]\nc) Los gobiernos regionales, sus programas y proyectos. [cite: 406][cite_start]\nd) Los gobiernos locales, sus programas y proyectos. [cite: 407][cite_start]\ne) Las universidades públicas. [cite: 408][cite_start]\nf) Las empresas del Estado pertenecientes a los tres niveles de gobierno. [cite: 409][cite_start]\ng) Los fondos constituidos total o parcialmente con recursos públicos, sean de derecho público o de derecho privado. [cite: 410][cite_start]\nh) El Seguro Social de Salud (ESSALUD). [cite: 411][cite_start]\ni) Las Fuerzas Armadas. [cite: 412][cite_start]\nj) La Policía Nacional del Perú. [cite: 413][cite_start]\nk) Los órganos desconcentrados. [cite: 414][cite_start]\n1) Las organizaciones creadas conforme al ordenamiento jurídico nacional con autonomía y capacidad para gestionar sus contrataciones. [cite: 415, 1]',
            ],
        ];
        
        foreach ($data as $item) {
            Article::create($item);
        }
    }
}
