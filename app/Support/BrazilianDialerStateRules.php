<?php

namespace App\Support;

use Illuminate\Support\Str;

class BrazilianDialerStateRules
{
    public static function defaultSchedule(): array
    {
        return [
            'monday' => ['enabled' => true, 'start' => '09:00', 'end' => '21:00'],
            'tuesday' => ['enabled' => true, 'start' => '09:00', 'end' => '21:00'],
            'wednesday' => ['enabled' => true, 'start' => '09:00', 'end' => '21:00'],
            'thursday' => ['enabled' => true, 'start' => '09:00', 'end' => '21:00'],
            'friday' => ['enabled' => true, 'start' => '09:00', 'end' => '21:00'],
            'saturday' => ['enabled' => true, 'start' => '10:00', 'end' => '16:00'],
            'sunday' => ['enabled' => false, 'start' => null, 'end' => null],
        ];
    }

    public static function referenceUrl(): string
    {
        return 'https://www.gov.br/anatel/pt-br/consumidor/telemarketing/perguntas-e-respostas';
    }

    public static function note(): string
    {
        return 'Janela inicial baseada no Codigo de Conduta do Telemarketing da Anatel/SART (09:00-21:00 em dias uteis e 10:00-16:00 aos sabados). Revise listas estaduais e municipais do Procon/nao me perturbe e ajuste a operacao com o time juridico antes de ativar campanhas.';
    }

    public static function definitions(): array
    {
        return [
            ['AC', 'Acre', 'America/Rio_Branco'],
            ['AL', 'Alagoas', 'America/Sao_Paulo'],
            ['AP', 'Amapa', 'America/Sao_Paulo'],
            ['AM', 'Amazonas', 'America/Manaus'],
            ['BA', 'Bahia', 'America/Sao_Paulo'],
            ['CE', 'Ceara', 'America/Sao_Paulo'],
            ['DF', 'Distrito Federal', 'America/Sao_Paulo'],
            ['ES', 'Espirito Santo', 'America/Sao_Paulo'],
            ['GO', 'Goias', 'America/Sao_Paulo'],
            ['MA', 'Maranhao', 'America/Sao_Paulo'],
            ['MT', 'Mato Grosso', 'America/Cuiaba'],
            ['MS', 'Mato Grosso do Sul', 'America/Campo_Grande'],
            ['MG', 'Minas Gerais', 'America/Sao_Paulo'],
            ['PA', 'Para', 'America/Sao_Paulo'],
            ['PB', 'Paraiba', 'America/Sao_Paulo'],
            ['PR', 'Parana', 'America/Sao_Paulo'],
            ['PE', 'Pernambuco', 'America/Sao_Paulo'],
            ['PI', 'Piaui', 'America/Sao_Paulo'],
            ['RJ', 'Rio de Janeiro', 'America/Sao_Paulo'],
            ['RN', 'Rio Grande do Norte', 'America/Sao_Paulo'],
            ['RS', 'Rio Grande do Sul', 'America/Sao_Paulo'],
            ['RO', 'Rondonia', 'America/Porto_Velho'],
            ['RR', 'Roraima', 'America/Boa_Vista'],
            ['SC', 'Santa Catarina', 'America/Sao_Paulo'],
            ['SP', 'Sao Paulo', 'America/Sao_Paulo'],
            ['SE', 'Sergipe', 'America/Sao_Paulo'],
            ['TO', 'Tocantins', 'America/Sao_Paulo'],
        ];
    }

    public static function seedRows(): array
    {
        $now = now();

        return collect(self::definitions())
            ->map(fn(array $definition) => [
                'uuid' => (string) Str::uuid(),
                'state_code' => $definition[0],
                'state_name' => $definition[1],
                'timezone' => $definition[2],
                'schedule' => json_encode(self::defaultSchedule(), JSON_THROW_ON_ERROR),
                'notes' => self::note(),
                'legal_reference_url' => self::referenceUrl(),
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();
    }
}
