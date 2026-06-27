<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ForumTopic;
use App\Models\User;

class ForumTopicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener el primer usuario o crear uno si no existe
        $user = User::first();
        
        if (!$user) {
            $user = User::create([
                'name' => 'Usuario Demo',
                'email' => 'demo@romanocc.com',
                'password' => bcrypt('password'),
            ]);
        }

        $topics = [
            [
                'title' => 'Bienvenidos al Foro de ROMANOCC',
                'content' => '¡Hola a todos! Este es el foro oficial de la aplicación ROMANOCC. Aquí podrán discutir temas relacionados con leyes, reglamentos y cualquier consulta legal. Esperamos que sea un espacio de aprendizaje y colaboración.',
                'user_id' => $user->id,
                'comments_count' => 3,
            ],
            [
                'title' => '¿Cómo interpretar el artículo 123 de la Constitución?',
                'content' => 'Hola, tengo una duda sobre la interpretación del artículo 123 de la Constitución Política. Específicamente sobre los derechos laborales y las condiciones de trabajo. ¿Alguien puede ayudarme a entender mejor este tema?',
                'user_id' => $user->id,
                'comments_count' => 5,
            ],
            [
                'title' => 'Nuevas regulaciones en materia de comercio electrónico',
                'content' => 'Recientemente se han publicado nuevas regulaciones sobre comercio electrónico que afectan a las pequeñas y medianas empresas. ¿Alguien tiene experiencia implementando estas nuevas normativas? Me gustaría compartir experiencias y mejores prácticas.',
                'user_id' => $user->id,
                'comments_count' => 2,
            ],
            [
                'title' => 'Consulta sobre procedimientos administrativos',
                'content' => 'Necesito ayuda con un procedimiento administrativo que estoy llevando a cabo. Específicamente sobre los plazos y la documentación requerida. ¿Hay algún abogado especializado en derecho administrativo que pueda orientarme?',
                'user_id' => $user->id,
                'comments_count' => 1,
            ],
            [
                'title' => 'Recomendaciones de libros de derecho constitucional',
                'content' => 'Estoy estudiando derecho constitucional y me gustaría que me recomienden algunos libros o recursos para profundizar en el tema. ¿Cuáles son los textos más importantes que debería leer?',
                'user_id' => $user->id,
                'comments_count' => 4,
            ],
        ];

        foreach ($topics as $topic) {
            ForumTopic::create($topic);
        }

        $this->command->info('Temas del foro creados exitosamente.');
    }
}
