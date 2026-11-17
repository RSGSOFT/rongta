<?php

namespace App\Livewire\Admin\Concerns;

/**
 * Russian validation messages for admin forms (the project has no lang/ru files).
 * Livewire picks up messages() automatically. Give fields readable names with
 * validationAttributes() or the third argument of $this->validate().
 */
trait RussianValidation
{
    protected function messages(): array
    {
        return $this->russianMessages();
    }

    /** The message list, for components that add their own messages() on top. */
    protected function russianMessages(): array
    {
        return [
            'required' => 'Заполните поле «:attribute».',
            'string' => 'Поле «:attribute» должно быть текстом.',
            'array' => 'Поле «:attribute» заполнено неверно.',
            'boolean' => 'Поле «:attribute» заполнено неверно.',
            'integer' => 'Поле «:attribute» должно быть целым числом.',
            'numeric' => 'Поле «:attribute» должно быть числом.',
            'email' => 'Укажите корректный адрес эл. почты.',
            'url' => 'Поле «:attribute» должно быть ссылкой, например https://example.com.',
            'date' => 'Поле «:attribute» должно быть датой.',
            'in' => 'Выбрано недопустимое значение поля «:attribute».',
            'exists' => 'Выбранное значение поля «:attribute» не найдено.',
            'unique' => 'Такое значение поля «:attribute» уже используется.',
            'regex' => 'Поле «:attribute» имеет неверный формат.',
            'alpha_dash' => 'Поле «:attribute»: только латинские буквы, цифры, дефис и подчёркивание.',
            'confirmed' => 'Поле «:attribute» не совпадает с подтверждением.',
            'same' => 'Поле «:attribute» не совпадает с подтверждением.',
            'max.string' => 'Поле «:attribute»: не больше :max символов.',
            'min.string' => 'Поле «:attribute»: не меньше :min символов.',
            'max.numeric' => 'Поле «:attribute»: не больше :max.',
            'min.numeric' => 'Поле «:attribute»: не меньше :min.',
            'max.array' => 'Поле «:attribute»: не больше :max элементов.',
            'max.file' => 'Файл «:attribute» слишком большой: не больше :max КБ.',
            'file' => 'Поле «:attribute» должно быть файлом.',
            'image' => 'Файл «:attribute» должен быть изображением.',
            'mimes' => 'Файл «:attribute»: допустимые типы: :values.',
            'mimetypes' => 'Файл «:attribute»: допустимые типы: :values.',
            'extensions' => 'Файл «:attribute»: допустимые расширения: :values.',
            'dimensions' => 'Изображение «:attribute» имеет недопустимые размеры.',
            'uploaded' => 'Не удалось загрузить файл «:attribute».',
            'password.letters' => 'Пароль должен содержать хотя бы одну букву.',
            'password.numbers' => 'Пароль должен содержать хотя бы одну цифру.',
            'password.mixed' => 'Пароль должен содержать строчные и прописные буквы.',
        ];
    }
}
