<?php

namespace Views;

class Error extends View {

    public function render() {
        $routeParagraph = '';
        if (method_exists($this->exception, 'getRoute')) {
            $routeParagraph = $this->exception->getRoute() ? "<p style='font-size: 20px; margin-bottom: 2vw; text-align: center'>{$this->exception->getRoute()}</p>" : "";
        }
        
        return <<<ERROR
        <html>
            <body style="display: flex; justify-content: center; align-items: center; flex-direction: column">
                $routeParagraph
                <p style="font-size: 24px; font-weight: bold; text-align: center">{$this->exception->getCode()} - {$this->exception->getMessage()}</p>
            </body>
        </html>
        ERROR;
    }
}