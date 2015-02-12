<?php
namespace CLIFramework\Completion;
use CLIFramework\Buffer;
use Exception;
use CLIFramework\Application;
use CLIFramework\ArgInfo;
use CLIFramework\CommandBase;

class BashGenerator
{
    public $app;

    /**
     * @var string $program
     */
    public $programName;

    /**
     * @var string $compName
     */
    public $compName;

    /**
     * @var string $bindName
     */
    public $bindName;

    public $buffer;

    public function __construct($app, $programName, $bindName, $compName)
    {
        $this->app = $app;
        $this->programName = $programName;
        $this->compName = $compName;
        $this->bindName = $bindName;
        $this->buffer = new Buffer;
    }

    public function output() {
        return $this->complete_application();
    }

    public function visible_commands(array $cmds) {
        $visible = array();
        foreach ($cmds as $name => $cmd) {
            if ( ! preg_match('#^_#', $name) ) {
                $visible[$name] = $cmd;
            }
        }
        return $visible;
    }

    public function render_argument_completion_values(ArgInfo $a) {
        if ($a->validValues || $a->suggestions) {
            $values = array();
            if ($a->validValues) {
                $values = $a->getValidValues();
            } elseif ($a->suggestions ) {
                $values = $a->getSuggestions();
            }
            return join(" ", $values);
        }
        return '';
    }

    public function complete_application() {
        $bindName = $this->bindName;
        $compName = $this->compName;

        $compPrefix = "__" . $compName;

        $buf = new Buffer;
        $buf->appendLines(array(
            "#!/bin/bash",
            "# bash completion script generated by CLIFramework",
            "# Web: http://github.com/c9s/php-CLIFramework",
            "# THIS IS AN AUTO-GENERATED FILE, PLEASE DON'T MODIFY THIS FILE DIRECTLY.",
        ));
        $buf->append('
# This function can be used to access a tokenized list of words
# on the command line:
#
#   __demo_reassemble_comp_words_by_ref \'=:\'
#   if test "${words_[cword_-1]}" = -w
#   then
#       ...
#   fi
#
# The argument should be a collection of characters from the list of
# word completion separators (COMP_WORDBREAKS) to treat as ordinary
# characters.
#
# This is roughly equivalent to going back in time and setting
# COMP_WORDBREAKS to exclude those characters.  The intent is to
# make option types like --date=<type> and <rev>:<path> easy to
# recognize by treating each shell word as a single token.
#
# It is best not to set COMP_WORDBREAKS directly because the value is
# shared with other completion scripts.  By the time the completion
# function gets called, COMP_WORDS has already been populated so local
# changes to COMP_WORDBREAKS have no effect.
#
# Output: words_, cword_, cur_.

__demo_reassemble_comp_words_by_ref()
{
    local exclude i j first
    # Which word separators to exclude?
    exclude="${1//[^$COMP_WORDBREAKS]}"
    cword_=$COMP_CWORD
    if [ -z "$exclude" ]; then
        words_=("${COMP_WORDS[@]}")
        return
    fi
    # List of word completion separators has shrunk;
    # re-assemble words to complete.
    for ((i=0, j=0; i < ${#COMP_WORDS[@]}; i++, j++)); do
        # Append each nonempty word consisting of just
        # word separator characters to the current word.
        first=t
        while
            [ $i -gt 0 ] &&
            [ -n "${COMP_WORDS[$i]}" ] &&
            # word consists of excluded word separators
            [ "${COMP_WORDS[$i]//[^$exclude]}" = "${COMP_WORDS[$i]}" ]
        do
            # Attach to the previous token,
            # unless the previous token is the command name.
            if [ $j -ge 2 ] && [ -n "$first" ]; then
                ((j--))
            fi
            first=
            words_[$j]=${words_[j]}${COMP_WORDS[i]}
            if [ $i = $COMP_CWORD ]; then
                cword_=$j
            fi
            if (($i < ${#COMP_WORDS[@]} - 1)); then
                ((i++))
            else
                # Done.
                return
            fi
        done
        words_[$j]=${words_[j]}${COMP_WORDS[i]}
        if [ $i = $COMP_CWORD ]; then
            cword_=$j
        fi
    done
}

if ! type _get_comp_words_by_ref >/dev/null 2>&1; then
_get_comp_words_by_ref ()
{
    local exclude cur_ words_ cword_
    if [ "$1" = "-n" ]; then
        exclude=$2
        shift 2
    fi
    __demo_reassemble_comp_words_by_ref "$exclude"
    cur_=${words_[cword_]}
    while [ $# -gt 0 ]; do
        case "$1" in
        cur)
            cur=$cur_
            ;;
        prev)
            prev=${words_[$cword_-1]}
            ;;
        words)
            words=("${words_[@]}")
            ;;
        cword)
            cword=$cword_
            ;;
        esac
        shift
    done
}
fi


# Generates completion reply, appending a space to possible completion words,
# if necessary.
# It accepts 1 to 4 arguments:
# 1: List of possible completion words.
# 2: A prefix to be added to each possible completion word (optional).
# 3: Generate possible completion matches for this word (optional).
# 4: A suffix to be appended to each possible completion word (optional).
__mycomp ()
{
	local cur_="${3-$cur}"

	case "$cur_" in
	--*=)
		;;
	*)
		local c i=0 IFS=$\' \t\n\'
		for c in $1; do
			c="$c${4-}"
			if [[ $c == "$cur_"* ]]; then
				case $c in
				--*=*|*.) ;;
				*) c="$c " ;;
				esac
				COMPREPLY[i++]="${2-}$c"
			fi
		done
		;;
	esac
}

__mycompappend ()
{
	local i=${#COMPREPLY[@]}
	for x in $1; do
		if [[ "$x" == "$3"* ]]; then
			COMPREPLY[i++]="$2$x$4"
		fi
	done
}
');

        $completeMeta =<<<"BASH"
__complete_meta ()
{
    local app="{$this->programName}"
    local command_signature=\$1
    local complete_for=\$2
    local arg=\$3  # could be "--dir", 0 for argument index
    local complete_type=\$4

    # When completing argument valid values, we need to eval
    IFS=\$'\n' lines=(\$(\$app meta --bash \$command_signature \$complete_for \$arg \$complete_type))

    # Get the first line to return the compreply
    if [[ \${lines[0]} == "#groups" ]] ; then
        # groups means we need to eval
        output=\$(\$app meta --bash \$command_signature \$complete_for \$arg \$complete_type)
        eval "\$output"

        # Here we should get two array: "labels" and "descriptions"
        # But in bash, we can only complete words, so we will abandon the "descriptions"
        # We use "*" expansion because we want to expand the whole array inside the same string
        COMPREPLY=(\$(compgen -W "\${labels[*]}" -- \$cur))
    else
        # Complete the rest lines as words
        COMPREPLY=(\$(compgen -W "\${lines[*]:1}" -- \$cur))
    fi
}


BASH;
        $buf->append($completeMeta);

        $this->generateCompleteFunction($buf, $this->app, $compPrefix);


        $signature = $this->app->getSignature();
        $funcSuffix = str_replace('.', '_',$signature);

        $buf->append("
{$compPrefix}_main_wrapper()
{
    {$compPrefix}_complete_{$funcSuffix} \"app\" 1
}
complete -o bashdefault -o default -o nospace -F {$compPrefix}_main_wrapper {$bindName} 2>/dev/null
");
        return $buf->__toString();
    }

    public function generateCompleteFunction(Buffer $buf, CommandBase $cmd, $compPrefix)
    {
        $signature = $cmd->getSignature();
        $funcSuffix = str_replace('.', '_',$signature);

        $buf->append("{$compPrefix}_complete_{$funcSuffix} ()");
        $buf->append('{
        local cur words cword prev
        _get_comp_words_by_ref -n =: cur words cword prev

        local command_signature=$1
        local command_index=$2

        # Output application command alias mapping 
        # aliases[ alias ] = command
        declare -A subcommand_alias

        # Define the command names
        declare -A subcommands

        # option names defines the available options of this command
        declare -A options
        # options_require_value: defines the required completion type for each
        # option that requires a value.
        declare -A options_require_value
        ');


        $subcommands = $cmd->getCommands();
        $subcommandAliasMap = array();
        foreach($subcommands as $subcommand) {
            foreach( $subcommand->aliases() as $alias) {
                $subcommandAliasMap[$alias] = $subcommand->getName();
            }
        }

        // Command signature is used for fetching meta information from the meta command.
        //
        //      subcommand_alias=(["a"]="add" ["c"]="commit")
        //
        $buf->append('subcommand_alias=(');
        foreach($subcommandAliasMap as $alias => $commandName) {
            // subcommand_alias=(["a"]="add" ["c"]="commit")
            $buf->append("[\"$alias\"]=\"$commandName\"");
        }
        $buf->append(")\n");

        // Generate a command description map
        //
        //  subcommands=(["add"]="command to add" ["commit"]="command to commit")
        //
        $buf->append('subcommands=(');
        foreach($subcommands as $subcommand) {
            $buf->append("[\"{$subcommand->getName()}\"]=\"" . addcslashes($subcommand->brief(), '"') . "\" ");
        }
        $buf->append(")\n");

        // Generate the bash array for command options
        //
        //      options=(["--debug"]=1 ["--verbose"]=1 ["--log-dir"]=1)
        //
        $options = $cmd->getOptionCollection();
        $buf->append('options=(');
        foreach($options as $option) {
            if ($option->short) {
                $buf->append('["-'. $option->short .'"]="1" ');
            }
            if ($option->long) {
                $buf->append('["--'. $option->long .'"]="1" ');
            }
        }
        $buf->append(")\n");

        //  options_require_value=(["--log-dir"]="__complete_directory")
        $buf->append("options_require_value=(");
        foreach($options as $option) {
            if ($option->required || $option->multiple) {
                if ($option->short ) {
                    $buf->append('["-'. $option->short .'"]="1" ');
                }
                if ($option->long ) {
                    $buf->append('["-'. $option->long .'"]="1" ');
                }
            }
        }
        $buf->append(")\n");

        // local argument_min_length=0
        $argInfos = $cmd->getArgumentsInfo();
        $buf->appendLine("local argument_min_length=" . count($argInfos));




        $buf->append('
    # Get the command name chain of the current input, e.g.
    # 
    #     app asset install [arg1] [arg2] [arg3]
    #     app commit add
    #  
    # The subcommand dispatch should be done in the command complete function,
    # not in the root completion function. 
    # We should pass the argument index to the complete function.

    # command_index=1 start from the first argument, not the application name
    # Find the command position
    local argument_index=0
    local i
    local command
    local found_options=0
    while [ $command_index -lt $cword ]; do
        i="${words[command_index]}"
        case "$i" in
            # Ignore options
            --=*) found_options=1 ;;
            --*) found_options=1 ;;
            -*) found_options=1 ;;
            *)
                # looks like my command, that\'s break the loop and dispatch to the next complete function
                if [[ -n "$i" && -n "${subcommands[$i]}" ]] ; then
                    command="$i"
                    break
                elif [[ -n "$i" && -n "${subcommand_alias[$i]}" ]] ; then
                    command="$i"
                    break
                else
                    # If the command is not found, check if the previous argument is an option expecting a value
                    # or it\'s an argument

                    # the previous argument (might be)
                    p="${words[command_index-1]}"

                    # not an option value, push to the argument list
                    if [[ -z "${options_require_value[$p]}" ]] ; then
                        ((argument_index++))
                    fi
                fi
            ;;
        esac
        ((command_index++))
    done
');

        $buf->append('
    # If the first command name is not found, we do complete...
    if [[ -z "$command" ]] ; then
        case "$cur" in
            # If the current argument $cur looks like an option, then we should complete
            --*)
                __mycomp "${!options[*]}"
                return
            ;;
            *)
                # The argument here can be an option value. e.g. --output-dir /tmp
                # The the previous one...
                if [[ -n "$prev" && -n "${options_require_value[$prev]}" ]] ; then
                    # TODO: local complete_type="${options_require_value[$prev]"}
                    __complete_meta "app.commit" "opt" "c" "valid-values"
                else
                    # If the command requires at least $argument_min_length to run, we check the argument
                    if [[ $argument_min_length > 0 ]] ; then
                        __complete_meta "app.commit" "opt" "c" "valid-values"
                    else
                        # If there is no argument support, then user is supposed to give a subcommand name or an option
                        __mycomp "${!options[*]} ${!subcommands[*]} ${!subcommand_alias[*]}"
                    fi
                fi
                return
            ;;
        esac
    else
        # We just found the first command, we are going to dispatch the completion handler to the next level...
        # Rewrite command alias to command name to get the correct response
        if [[ -n "${subcommand_alias[$command]}" ]] ; then
            command="${subcommand_alias[$command]}"
        fi
        local completion_func="__demo_comp_${command//-/_}"

        # declare the completion function name and dispatch rest arguments to the complete function
        command_signature="${command_signature}.${command}"
        declare -f $completion_func >/dev/null && $completion_func $command_signature $command_index && return
    fi
');


        // Epilog
        $buf->append("};\n");
    }

}


