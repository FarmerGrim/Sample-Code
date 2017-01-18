TITLE 360Proj6-VidGame.asm		(360Proj6-VidGame.asm)

;// Program Description: The project involves creating a ‘grid based’ video game
;//	 that is played in a console window. The standard console window is sized to 
;//	 allow 25 rows of 80 characters each. This will form the grid for the game with
;//	 each character position being a location that a game character can occupy.
;//	 The requirements below identify the basic functions that must be accomplished 
;//	 in your game. Beyond that the game can be whatever you want it to be. The rules
;//	 of the ‘Zombies’ game is provided which can be used as the basis of your game 
;//	 if you do not want to invent a new one.
;// Author:	Toby Hummel
;// Date Created: 09-06-11
;// Last Modification Date: 10-14-11 

INCLUDE Irvine32.inc

.data

playerPos		WORD ?			;// player position
zombiePos		WORD ?			;// zombie (AI) position	
poolPos		WORD 0525h		;// stationary pool position
playrchar  = 01h					;// character initialization
zomchar  = 0d7h				
poolchar = 0b1h
wallchar = 0DBh
nochar   = 20h
delval		WORD 2000		
delcnt		WORD ?
gameboard		BYTE 25*80 dup(020h)
doneflag		BYTE 0			;// set doneflag to 0 (continue if flag=0)

;/////////////////////// Messages ///////////////////////////////////////////////////////////
SplshMsg	BYTE "	+==========================================================+",0dh,0ah,
			"	                                                            ",0dh,0ah,
               "	      Welcome to                                            ",0dh,0ah,
			"	                                                            ",0dh,0ah,
			"	    SSSS    PPPPP   LL        AA     SSSS   HH   HH   !!    ",0dh,0ah,
			"	   SS  SS   PP  PP  LL       AAAA   SS  SS  HH   HH   !!    ",0dh,0ah,0
SplshMsg2 BYTE "	    SS      PP  PP  LL      AA  AA   SS     HH   HH   !!    ",0dh,0ah,
               "	     SSS    PPPPP   LL      AAAAAA    SSS   HHHHHHH   !!    ",0dh,0ah,
			"	       SS   PP      LL      AA  AA      SS  HH   HH   !!    ",0dh,0ah,
			"	   SS  SS   PP      LL      AA  AA  SS  SS  HH   HH         ",0dh,0ah,0
SplshMsg3 BYTE	"	    SSSS    PP      LLLLLL  AA  AA   SSSS   HH   HH   00    ",0dh,0ah,
			"	                                                            ",0dh,0ah,
			"	       ...a game of drowning zombies and takin' names!      ",0dh,0ah,
			"	                                                            ",0dh,0ah,
			"	+==========================================================+",0dh,0ah,09h,0

AskHowMsg	BYTE 0dh,0ah,"		Do you need instructions? {Y/N}:",0
InstrMsg	BYTE	"	+=====-How to play:-=============+",0dh,0ah,
			"	-You control the (",playrchar,")",0dh,0ah,
			"	-Don't get eaten by the zombie (",zomchar,")",0dh,0ah,
			"	-Don't drown in the pool (",poolchar,")",0dh,0ah,
			"	-Position yourself opposite the",0ah,0dh,
			"	   pool (",poolchar,") from the stupid",0ah,0dh,
			"	   zombie to drown it!",0ah,0dh,
			"	+================================+",0dh,0ah,0
InstrMsg2 BYTE "	Use the arrow keys to move:",0dh,0ah,
			"		Up:    ",1eh,0dh,0ah, 
			"		Down:  ",1fh,0dh,0ah,
			"		Left:  ",10h,0dh,0ah,
			"		Right: ",11h,0dh,0ah,
			"		X:     Exit",0dh,0ah,
			"	+================================+",0dh,0ah,09h,0

OptnMsg	BYTE	"	+=====-Options:-=================+",0dh,0ah,
			"	   1= slow, 2= medium, 3= fast.",0dh,0ah,
			"	+================================+",0dh,0ah,
			"	     Choose gamespeed {1-3}:",0
UdiedMsg	BYTE	"	YOU LOSE! You fell in the pool and drowned!",0dh,0ah,0
atemsg	BYTE	"	YOU LOSE! The zombie ate your brains!",0dh,0ah,0
manwonmsg BYTE "	YOU WIN! You drowned the zombie!",0ah,0dh,0
AgainMsg	BYTE 0dh,0ah,"		Would you like to play again? {Y/N}:",0
ThanksMsg	BYTE 0dh,0ah,"		Thanks for playing SPLASH!",0ah,0dh,0

;///////// Print center Macro /////////////
;// preserves edx, uses edx to print roughly centered message.
mPrint MACRO Msg
	push edx
	mov  edx, OFFSET Msg
	call WriteString
	pop  edx
ENDM

;/////// Move Cursor Macro ///////////
;// preserves edx, uses dx to move cursor
mMoveCurs MACRO row,col
	push edx
	mov  dh,row 			;// row 
	mov  dl,col			;// column 
	call Gotoxy
	pop  edx
ENDM

;/////// MAIN ////////////////////////////////////////////////////////////////
.code
main PROC
	call Clrscr			;// Clears the screen
	call Splashy			;// Displays the splash screen
	call crlf
	call InstructMe		;// Displays the instructions
	jmp  goon
newgame:
	mov  doneflag,0		;// set doneflag to 0 (continue if flag=0)
	mPrint AskHowMsg		;// Do you need instructions
	call ReadChar
	and  al,11011111b		;// Uppercase it
	cmp  al,"Y"			;// if yes then
	jne  goon
	call InstructMe		;// display instructions
goon:									
	call options			;// display/get options
	call setupboard		;// set up game board
gameloop:					;// start game loop
	call ReadKey			;// see if man needs to move
	jz   dozombie			;// if yes then
	call moveman			;// move him
	cmp  doneflag,0		;// (continue if flag=0)
	jne  gameover			;// if flag not = 0 
dozombie:
	dec  delcnt			;// subtract 1 form delay counter
	jnz  gameloop			;// jump to the gameloop label unless delcnt is 0
	mov  ax,delval				
	mov  delcnt,ax			;// delcnt= delval(user input option)			
	call movezom			;// move zombie
	cmp  doneflag,0
	je  gameloop			;// end loop

gameover:
	mov  ax, 700			;// set value for delay
	call delay			;// delay to prevent leftover user input from skipping msgs
	mMoveCurs 11,15		;// move cursor macro to justify messages
	call waitmsg			;// again keep users from accidently skipping msgs
	mPrint AgainMsg		;// ask user if they want to play again.
	call ReadChar				
	and  al,11011111b
	cmp  al,"N"
	jne  newgame			;// jump to top of gameloop unless user pressed "N"
	mPrint ThanksMsg		;// Thank user for playing
	mov  ax, 1000
	call delay			;// again keep user from skipping thanks msg
	mMoveCurs 14,15

	exit		;// exit
main ENDP

;///////// Splash Screen //////////////
;// Displays the intro splash screen messages.
splashy PROC
	call clrscr
	call crlf				;// clear screen and line feed
	mPrint SplshMsg
	mPrint SplshMsg2		;// macro print splash messages
	mPrint SplshMsg3
	call waitmsg
	ret
splashy ENDP

;///////// Instructions //////////////
;// Displays instruction msg
instructMe PROC
	call clrscr
	call crlf
	call crlf
	mPrint InstrMsg		;// macro print instruction msgs
	mPrint InstrMsg2
	call waitmsg

	ret
instructMe ENDP

;//////// Options ////////////////////
;// Displays options and accepts user selected gamespeed.
options PROC
	call clrscr
	call crlf
	call crlf
	mPrint OptnMsg			;// print opt message
	call ReadDec			;// read decimal input
	cmp  eax,1			;// if user enters 1 for slow...
	jne  faster
	mov  delval,2500		;// ...set delay val to 3000
	jmp  speedset			;// jump past other speed set
faster:
	cmp  eax,2			;// if user enters 2 for med...
	jne  fasteryet	
	mov  delval,1700		;// ..set delay val to 1800
	jmp  speedset			;// jump past other speed set
fasteryet:
	cmp  eax,3			;// if user has a deathwish(fast)..
	jne  speedy			
	mov  delval,1000		;// ...set del val to fast
	jmp  speedset
speedy:
	mov  delval,700		;// set delval to super fast if no valid input

speedset:					;// jump here when set then return
	ret
options ENDP

;//////// Setup Board /////////////
;// sets up the gameboard with all blanks exept walls, player, and zomb
;// uses ecx, dx, al, and esi
	
setupboard PROC
	call clrscr
;// place player
	mov  ecx,lengthof Gameboard	;//set counter reg to lenght of gameboard array
	mov  dh,20
	mov  dl,55
	mov  PlayerPos,dx			;// set player position
	call gotoxy				
	mov  al,playrchar
	call writeChar				;// place player character at player pos
	call getindex				;// get array index where player char is
	mov  Gameboard[esi],playrchar	;// and save it on board array
;// place zombie
	mov  dh,15
	mov  dl,12
	mov  ZombiePos,dx			;// set Zomb pos
	call gotoxy
	mov  al,zomchar
	call writeChar				;// disp zomb char at zomb pos to screen
	call getindex				;// get array index where zomb char is
	mov  Gameboard[esi],zomchar	;// and save it to board array
;// Place Pool (obstacle)
	mov  dx,PoolPos			;// pool position
	call gotoxy
	mov  al,poolchar
	call writeChar				;// display pool char on screen
	call getindex				;// get array index of pool
	mov  Gameboard[esi],poolchar	;// and save it to board array
	mov	ax,delval				;// move delay val to delay cnt
	mov  delcnt,ax

	ret
setupboard ENDP

;///////////// MovePlayer /////////////
;// moves the player about the gameboard array
;// checks for exit condition
moveman PROC
	mov  dx,PlayerPos
	and  al,11011111b		;//Uppercase it
	jz   Arrowcheck
	cmp  al,"X"			;// check input for 'X' exit condition
	jne  Arrowcheck		;// otherwise check for direction input
	mov  doneflag,47		;// if X was entered set doneflag for exit
	jmp  godone
Arrowcheck:
	cmp  ah,48h		;// check input for up arrow and jump appropriatly
	je   goup
	cmp  ah,50h		;// check input for down arrow
	je   godown
	cmp  ah,4Bh		;// check input for left arrow
	je   goleft
	cmp  ah,4Dh		;// check input for right arrow
	je   goright
	jmp  godone		;// jump to return
goup:
	cmp  dh,0			;// cant move up if already at top
	je   godone
	dec  dh			;// dec row register by 1
	jmp  gomove		;// jump to move

godown:
	cmp  dh,24		;// cant move down if already at bottom
	je   godone
     inc  dh			;// add 1 to row reg
	jmp  gomove		;// jump to move

goleft:
	cmp  dl,0			;// if on left edge of screen
	je   leftwrap		;// jump to left wrap(ie, move to far right side)
	dec  dl			;// otherwise dec column by 1
	jmp  gomove		;// jump to move
leftwrap:
	mov  dl,79		;// set column to far right
	jmp  gomove		;// jmp to move

goright:
	cmp  dl,79		;// if already on right edge..
	je   rightwrap		;// jump to rt wrap.
	inc  dl			;// otherwise add 1 to column reg
	jmp  gomove		;// jump to move label
rightwrap:
	mov  dl,0			;// set column to far left

	;// check if movement is valid and move otherwise godone
gomove:
	call getindex				;// get current position index
	cmp  Gameboard[esi],nochar	;// if pos isn't blank
	jne  mannogo				;// man can't go there
	mov  Gameboard[esi],playrchar	;// let the man move to index
	push edx					;// dont mess up edx(push on stack)
	mov  dx,PlayerPos			;// load player position
	call getindex				;// get the pos index
	mov  Gameboard[esi],nochar	;// blank out former player pos in gameboard array
	call gotoxy
	mov  al,nochar				
	call writeChar				;// write blank to screen
	pop  edx					;// pop edx from stack
	mov  PlayerPos,dx			;// set new player pos
	call gotoxy				
	mov  al,playrchar			;// write player char to screen
	call writeChar
	jmp  godone
;// check why player can't go there
mannogo:
	cmp  Gameboard[esi],wallchar	;// cant go b/c of wall
	je   godone				;// go done
	cmp  Gameboard[esi],zomchar	;// if its zombie
	je   maneaten				;// do the player eaten stuff
	cmp  Gameboard[esi],poolchar	;// fell in the pool
	je   mandied
	jmp  godone
maneaten:					;//man was eaten by zombie
	mov al,zomchar			;// man turns into zombie when eaten
	call writeChar
	mMoveCurs 9,15			;// moves curser macro for formatting msg
	mPrint ateMsg			;// notify user they were eaten
	mov  doneflag,40		;// set flag for overall gameloop
	mov  eax,1800			;// delay to prevent user from skipping msg
	call delay
	jmp  godone	
mandied:
	;// notify user they died	
	mMoveCurs 9,15
	mPrint UdiedMsg
	mov  eax,1800
	call delay
	mov  doneflag,41		;// set gameloop doneflag
	
godone:
	ret
moveman ENDP

;//////// Move Zombie //////////////
movezom PROC
	mov dx,ZombiePos			;// load zomb pos to dx
	call gotoxy				;// blank out where he used to be
	mov  al,nochar				
	call writeChar				;// write blank
	call getindex				;// get curr index
	mov  Gameboard[esi],nochar	;// set blank in gameboard array
;// compare zombie pos to player pos and move closer
	mov bx,PlayerPos			
	cmp dl,bl
	ja  zomleft		
	jb  zomright
	jmp checkupdown
zomleft:
	dec  dl			;// mov zomb left
	jmp checkupdown
zomright:
	inc  dl

checkupdown:
	cmp dh,bh
	ja  zomup			;// check  whether man is higher/lower than zomb
	jb  zomdown
	jmp movhim		
zomup:
	dec  dh			;// up
	jmp movhim
zomdown:
	inc  dh			;// down
	jmp  movhim

movhim:				;// mov the zombie
     call getindex
	cmp  Gameboard[esi],nochar
	jne  whynot		;// see why zomb cant move
	mov  ZombiePos,dx	;// if blank zomb can move here
stayput:				
	mov  Gameboard[esi],zomchar
	call gotoxy
	mov  al,zomchar	;// write zomb char to screen and array
	call writeChar
	jmp  zommoved		;// jump to moved

whynot:				;// check why zombie cant move here
	cmp  Gameboard[esi],wallchar		;// is it because of a wall?
	jne  zompool		;// jump and check pool
	mov  dx,ZombiePos
	call getindex
	jmp  stayput		;// if was wall stayput/ try again
zompool:				;// if zomb hits pool he drowns
	cmp  Gameboard[esi],poolchar
	jne  zomman		;// not pool? check man
	mMoveCurs 9,15		;// if pool give you won msg
	mPrint manwonMsg
	mov  eax,1800
	call delay
	mov  doneflag,50	;// set done flag for overall game loop
	jmp  zommoved		;// jump to return
zomman:	
	cmp  Gameboard[esi],playrchar
	jne  stayput
	mMoveCurs 9,15
	mPrint atemsg		;// tell the player they lost
	mov  doneflag,51	;// set doneflag for game loop
	mov  eax,1800
	call delay		;// delay gives user time to read

zommoved:
	ret
movezom ENDP

;////// Get Index ///////////////////////////////////////////
;//** Assume coming in DX has a row, column - Dont mess it up!
;//** This messes with eax and ebx
;//** return index in esi
getIndex PROC

	push edx
	mov  ax,80			;//80 columns in the grid
	mov  bx,0				;//clear the upper byte
	mov  bl,dh
	mul  bx
	pop  edx
	mov  ebx,0
	mov  bl,dl
	add  eax,ebx
	mov  esi,eax

	ret
getIndex ENDP

END main

comment;//
//1.	The game must start with a splash screen introducing the game.  There should be 
//		some basic instructions available to explain how to play the game.
//2.	To begin game play the window should be cleared and reset to whatever state the 
//	game requires to begin.  You will need to keep track of the state of the grid so 
//	you know what character is where at all times.
//3.	There should be a main game character that is controlled by the keyboard.  The 
//	character should be able to move multiple directions.  Movement beyond an edge of 
//	the console window should be either prevented or handled gracefully.  
//	(any ASCII character will do)
//4.	There should be some kind of obstacles that the character must maneuver around.
//5.	There should be at least one character that moves on its own in some manner.
//6.	There should be a definite point to winning/completing the game.  A success or 
//	failure message (or some kind of status) should be displayed upon completion of the game.
//7.	There should be a repeat option to play the game again.
//8.	You should code everything using MASM instructions and NOT use the shortcut directives 
//	for conditional control (.IF, .WHILE, etc.)
;