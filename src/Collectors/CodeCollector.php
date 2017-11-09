<?php
namespace Shieldfy\Collectors;
use Shieldfy\Config;
class CodeCollector implements Collectable
{
	/**
	 * @var code Code block
	 * @var stack Stack trace
	 */
	private $code = [];
	private $stack = [];

    protected $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

	/**
	 * Push stack trace
	 * @param Array|array $stack 
	 * @return void
	 */
	public function pushStack(Array $stack = array())
	{
		$this->stack = $stack;
        //exit;
		return $this;
	}

	public function collectFromStack()
	{
        $stack = array_reverse($this->stack);
        //array_shift($trace); // remove {main}
        //array_pop($trace); // remove call to the internal caller
        
        foreach($this->stack as $trace):
            if(!isset($trace['file'])) continue;

            if(strpos($trace['file'], $this->config['paths']['vendors']) === false){
                //this is probably our guy ( the last file called outside vendor file)
                return [
                    'stack' => $stack,
                    'code'  => $this->collectFromFile($trace['file'],$trace['line'])
                ];
            }
            
        endforeach;
        //ddb($trace,1);
        // foreach ($trace as $file) {
        //     foreach ($this->filesExceptionsList as $fileException) {
        //         if (strpos($file, $fileException) !== false) {
        //             continue 2;
        //         }
        //     }
        //     $shortedStack = $file;
        // }

        // //extract file & number
        // if (preg_match('/#[0-9]+\s*([^\s\(]+)\s*\(([0-9]+)\)/U', $shortedStack, $matches)) {
        //     $this->code = $this->collectFromFile($matches[1], $matches[2]);
        // }

        return [
            'stack' => $stack,
            'code'  => []
        ];
	}

	public function collectFromFile($filePath = '', $line = '')
    {
        if ($filePath && file_exists($filePath)) {
            $content = file($filePath);
            array_unshift($content, 'x');
            unset($content[0]);
            $start = $line - 4;
            $content = array_slice($content, $start < 0 ? 0 : $start, 7, true);
        } else {
            $content = array("Cannot open the file ($filePath) in which the vulnerability exists ");
        }

        $this->code = [
            'file' => $filePath,
            'line' => $line,
            'content'=> $content
        ];
        return $this->code;
    }

    public function collectFromText($text = '', $value)
    {
        $content = explode("\n", $text);
        $line = 0;
        $code = [];
        for ($i=0; $i < count($content); $i++) {
            if (stripos($content[$i], $value) !== false) {
                $line = $i;
                $start = $i - 4;
                $code = array_slice($content, $start < 0 ? 0 : $start, 7, true);
                break;
            }
        }

        $this->code = [
            'file' => 'none',
            'line' => $line + 1, //to fix array 0 index
            'content' => $code
        ];
        return $this->code;
    }

    public function getInfo()
    {
        return [
        	'code' => $this->code,
        	'stack' => $this->stack
        ];
    }
}