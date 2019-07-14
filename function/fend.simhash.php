<?php

/**
 * Created by PhpStorm.
 * User: flyfish
 * Date: 2017/11/20
 * Time: 下午5:49
 */
class GendSimhash
{
    private $thisHashCode;
    private $participle = array();
    public static function factory()
    {
        return new self();
    }
    private function getParticiple($str)
    {
        $index = 0;
        $string = "";
        $chinese = "";
        $vis = false;   //标记是否上一个字符是中文
        $tmp = array();
        for ($i = 0; $i < strlen($str); $i++) {
            if (strlen(substr($str, $index, 1)) > 0) {
                if (ord(substr($str, $index, 1)) < 192) {   //如果为英文则取1个字节
                    if ($vis == true) {   //如果中文之间存在分隔符或者英文字符，则将两部分中文分割
                        if (strlen($chinese) != 0) array_push($tmp, $chinese);
                        $chinese = '';
                        $vis = false;
                    }
                    $string .= substr($str, $index, 1);
                    $index++;
                } else {
                    if (ord(substr($str, $index, 1)) < 224) {
                        $chinese .= substr($str, $index, 2);
                        $index += 2;
                    } else {
                        $chinese .= substr($str, $index, 3);
                        $index += 3;
                    }
                    $vis = true;
                }
            }
        }
        $this->participle = mb_split('[\[\]:\. ;/,]', $string);
        if (strlen($chinese) != 0) array_push($tmp, $chinese);
        foreach ($tmp as $i) {      //对中文进行分词
            $index = 0;
            $tmpLen = ord(substr($i, $index, 1));
            $tmpString = substr($i, $index, ($tmpLen < 224 ? 2 : 3));
            $index += ($tmpLen < 224 ? 2 : 3);
            for ($j = 1; $j < strlen($i); $j++) {
                if (strlen(substr($i, $index, 1)) > 0) {
                    $tmpLen = ord(substr($i, $index, 1));
                    $tmpString .= substr($i, $index, ($tmpLen < 224 ? 2 : 3));
                    array_push($this->participle, $tmpString);
                    $tmpString = substr($i, $index, ($tmpLen < 224 ? 2 : 3));
                    $index += ($tmpLen < 224 ? 2 : 3);
                }
            }
        }
        $index = 0;
        foreach ($this->participle as $i){
            if(strlen($i) != 0){
                $this->participle[$index ++] = $i;
            }
        }
        while(count($this->participle) > $index) array_pop($this->participle);
    }

    public function getHashCode($str)
    {
        $this->getParticiple($str);
        $cnt = array();
        for($i = 0;$i < 32;$i ++){
            array_push($cnt,0);
        }
        foreach ($this->participle as $index){
            $val = decbin(hexdec(hash('crc32' , $index)));
            $diff = 32 - strlen($val);
            for($i = 0;$i != strlen($val);$i ++){
                $cnt[$i + $diff] += ($val[$i] == '1' ? 1 : -1);     //因为可能转换之后val不是32位的(没那么大),所以计算时候需要考虑对齐
            }
        }
        $this->thisHashCode = 0;
        foreach($cnt as $index){
            if($index <= 0) $this->thisHashCode = $this->thisHashCode << 1;
            else            $this->thisHashCode = $this->thisHashCode << 1 | 1;
        }
        return $this->thisHashCode;
    }

    public function getHammingDistance($index,$hashCode)
    {
        $bb = isset($GLOBALS['hashcode'][$index])?$GLOBALS['hashcode'][$index]:0 ^ $hashCode;
        $C55 = 0x5555555555555555;
        $C33 = 0x3333333333333333;
        $C0F = 0x0f0f0f0f0f0f0f0f;
        $C01 = 0x0101010101010101;
        $bb -= ($bb >> 1) & $C55;
        $bb = ($bb & $C33) + (($bb >> 2) & $C33);
        $bb = ($bb + ($bb >> 4)) & $C0F;
        $ans = ($bb * $C01) >> 56;
        return 1.0 - $ans / 32.0;
    }
}