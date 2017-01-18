// 380 GL P3 Racetrack Hummel.cpp : Defines the entry point for the console application.
//
////////////////////////////
// Toby Hummel			    	//
// CISS 380				      	//
// Project  "Racetrack" 	//	
// 10/10/12				      	//
////////////////////////////

#include "stdafx.h"
#include <iostream>
#include <GL/glut.h>
#include <GL/gl.h>
#include <cmath>
#include <ctime>

using std::cout; using std::endl;

static bool toggle1stPerson= true;
static const float PI=3.14159;
static GLfloat inRadius= 20., outRadius= 30.; 
static GLfloat stripInRad= 24.9, stripOutRad= 25.1;
static GLfloat prpx= 35, prpy= -25, prpz= 1.7;   // Camera coords
static GLfloat vrpx= 34, vrpy= -25, vrpz= 1.7;   // reference pt. coords
static GLfloat vupx= 0, vupy= 0, vupz= 1;
static GLfloat viewAngle= 60, aspRatio= (4/3.5); // perspective params
static GLfloat nearz= .8, farz= 245;
static GLfloat dtheta= 0, speed= 0.;
static GLfloat direction= PI, xMouse;
static GLint windowWidth = 800, windowHeight = 600;
///// Lighting /////
static bool toggleNight= false, toggleLight0= false, toggleLight1= false;
static const GLfloat day[4]={1.0,1.0,1.0, 1.0};  // ambient light source intens (max)
static const GLfloat night[4]= {.3,.3,.3,1};
static const GLfloat stripes[4]={.9,.9,.9, 1};
static const GLfloat track[4]={.3,.3,.3, 1};
static const GLfloat light0Posn[4]={0,-45,16, 1};   // grandstand light params
static const GLfloat light0Dif[4]={.8,.8,.8, 1};
static const GLfloat light1Dif[4]={.9,.9,.9, 1};   // headlight params
static GLfloat light1Posn[4]= {vrpx,vrpy,1, 1};
static GLfloat light1Dir[4]= {0,0,1, 1};
///// Texture maps /////
static GLubyte finishMap[64][64][3];
static GLubyte bricksMap[16][16][3];
static GLubyte c;


void drawSemiCirc(void)   // draw track curves
{ 
  GLfloat theta, x, y;
  GLboolean draw= true;

  glMaterialfv(GL_FRONT_AND_BACK, GL_AMBIENT_AND_DIFFUSE, track); // Dark grey for track
  glBegin(GL_QUAD_STRIP);
  for (int i=0; i<46; i++){ // loop to calculate each of the vertices.
    theta=(4*i-90)*PI/180;   //simple loop, complex angle calculation
    x= inRadius*cos(theta);
    y= inRadius*sin(theta);
    glVertex3f(x,y,0.01);        //inner vertex
    x= outRadius*cos(theta);
    y= outRadius*sin(theta);
    glVertex3f(x,y,0.01);        //outer vertex
  }
  glEnd();
  
  glMaterialfv(GL_FRONT_AND_BACK, GL_AMBIENT_AND_DIFFUSE, stripes);  // light grey for stripes
  for (int i=1; i<45; i++) { // loop to calculate each of the vertices.  
    if(draw == 0) draw= !draw;    // prevents consecutive stripes
    else {
      glBegin(GL_QUADS);
        theta=(4*i-90)*PI/180;   // angle in degrees
        x= stripOutRad*cos(theta);
        y= stripOutRad*sin(theta);  // first two vertices
        glVertex3f(x,y,0.02);
        x= stripInRad*cos(theta);
        y= stripInRad*sin(theta);
        glVertex3f(x,y,0.02);

        theta=(4*i-86)*PI/180;   // advance theta by 4 degrees
        x= stripInRad*cos(theta);
        y= stripInRad*sin(theta);
        glVertex3f(x,y,0.02);
        x= stripOutRad*cos(theta);  // second two vertices
        y= stripOutRad*sin(theta);
        glVertex3f(x,y,0.02);
      glEnd();
      draw= !draw;    // keep from drawing adjacent stripes
    }
  }
}

void drawRect(void)  // draw straight portions of track 1x1m polygons
{
  glMaterialfv(GL_FRONT_AND_BACK, GL_AMBIENT_AND_DIFFUSE, track); // Dark grey for track
  float x,y;
  // draw ground in little pieces so positional light angle
  // won't be too wrong for any vertex, for better lighting.
  for (x=-50.; x<50.; x++) {
    glBegin(GL_QUAD_STRIP);    // start a polygon primitive
    for (y=inRadius; y<outRadius+1; y++) {
      glVertex3f(x+1, y, 0.01);
      glVertex3f(x, y, 0.01);
    }
    glEnd();  // end of quad strip
  }
  // Draw center stripes
  glMaterialfv(GL_FRONT_AND_BACK, GL_AMBIENT_AND_DIFFUSE, stripes); // light grey for lines
  for(int i=1; i<100; i+=5) {
    glBegin(GL_POLYGON);
      glVertex3f(i-50,stripOutRad,0.02);
      glVertex3f(i-50,stripInRad,0.02);
      glVertex3f(i-47,stripInRad,0.02);
      glVertex3f(i-47,stripOutRad,0.02);
    glEnd();  
  }
}

void drawGround(void)  // draw green ground in 1x1m polygons
{
  const GLfloat grass[4]={.132,.54,.132, 1};  //green for grass
  glMaterialfv(GL_FRONT_AND_BACK, GL_AMBIENT_AND_DIFFUSE, grass);
  float x,y;
   // draw ground in little pieces so positional light angle
   // won't be too wrong for any vertex, for better lighting.
   for (x=-150.; x<151.; x++) {
     glBegin(GL_QUAD_STRIP);  // start a polygon primitiv
     for (y=-105.; y<106.; y++) {
       glVertex3f(x+1, y, 0);
       glVertex3f(x, y, 0);
     }
     glEnd();  // end of quad strip
   }
}

void drawFinishLine(void)
{
  glTexImage2D(GL_TEXTURE_2D, 0, 3, 64, 64, 0, GL_RGB, GL_UNSIGNED_BYTE, finishMap);
  glTexEnvf(GL_TEXTURE_ENV, GL_TEXTURE_ENV_MODE, GL_DECAL);  // decal mode
  glTexParameterf(GL_TEXTURE_2D, GL_TEXTURE_WRAP_S, GL_REPEAT);  // repeat to fill
  glTexParameterf(GL_TEXTURE_2D, GL_TEXTURE_WRAP_T, GL_REPEAT);
  glTexParameterf(GL_TEXTURE_2D, GL_TEXTURE_MAG_FILTER, GL_LINEAR);
  glTexParameterf(GL_TEXTURE_2D, GL_TEXTURE_MIN_FILTER, GL_LINEAR);
  int i,j;
  for (i=0; i<64; i++) {
    for (j=0; j<64; j++) {
      /* make alternating black/white squares 8 pixels on a side */
      /* look at the 4th bit of i and j. If the 'exclusive-or' of these 2 bits is
         not zero, we're in a white square. ELse we're in a black square*/
      c=((((i&8)==0)^((j&8)==0)))*255;     /* c is 0 or 255 */
      finishMap[i][j][0]=(GLubyte)c; 
      finishMap[i][j][1]=(GLubyte)c; 
      finishMap[i][j][2]=(GLubyte)c; 
    }
  }
  glEnable(GL_TEXTURE_2D);
  glBegin(GL_POLYGON);
    glTexCoord2f(0.,0.); glVertex3f(-1.5,-outRadius,0.021);
    glTexCoord2f(1.,0.); glVertex3f(-1.5,-inRadius,0.021);
    glTexCoord2f(1.,.5); glVertex3f(1.5,-inRadius,0.021);
    glTexCoord2f(0.,.5); glVertex3f(1.5,-outRadius,0.021);
  glEnd();
  glDisable(GL_TEXTURE_2D);
}

void drawBricks(void)
{
  glTexImage2D(GL_TEXTURE_2D, 0, 3, 16, 16, 0, GL_RGB, GL_UNSIGNED_BYTE, bricksMap);
  glTexEnvf(GL_TEXTURE_ENV, GL_TEXTURE_ENV_MODE, GL_BLEND);  //blend mode
  glTexParameterf(GL_TEXTURE_2D, GL_TEXTURE_WRAP_S, GL_REPEAT);  // repeat to fill
  glTexParameterf(GL_TEXTURE_2D, GL_TEXTURE_WRAP_T, GL_REPEAT);
  glTexParameterf(GL_TEXTURE_2D, GL_TEXTURE_MAG_FILTER, GL_NEAREST);
  glTexParameterf(GL_TEXTURE_2D, GL_TEXTURE_MIN_FILTER, GL_NEAREST);
  int i,j;
   /* first set all texels to (0,0,0) */
  for (i=0; i<16; i++) {
    for (j=0; j<16; j++) {
      bricksMap[i][j][0]=(GLubyte)0;
      bricksMap[i][j][1]=(GLubyte)0;
      bricksMap[i][j][2]=(GLubyte)0;
    }
  }    /* center horiz mortar  line, 2 pixels wide, in rows 8 and 9 */
  for (i=0; i<16; i++) {
    for (j=8; j<9; j++) {
      bricksMap[i][j][0]=(GLubyte)255;
      bricksMap[i][j][1]=(GLubyte)255;
      bricksMap[i][j][2]=(GLubyte)255;
    }
  }    /* create lower horiz mortar line, 2 pixels wide, in rows 0 and 1 */
  for (i=0; i<16; i++) {
    for (j=0; j<1; j++) {
      bricksMap[i][j][0]=(GLubyte)255;
      bricksMap[i][j][1]=(GLubyte)255;
      bricksMap[i][j][2]=(GLubyte)255;
    }
  }    /* upper left vert mortar line in cols 8 and 9 */
  for (i=8; i<9; i++) {
    for (j=8; j<16; j++) {
      bricksMap[i][j][0]=(GLubyte)255;
      bricksMap[i][j][1]=(GLubyte)255;
      bricksMap[i][j][2]=(GLubyte)255;
    }
  }    // create lower left vert mortar line in cols 0 and 1
  for (i=0; i<1; i++) {
    for (j=0; j<8; j++) {
      bricksMap[i][j][0]=(GLubyte)255;
      bricksMap[i][j][1]=(GLubyte)255;
      bricksMap[i][j][2]=(GLubyte)255;
    }
  }
}

void drawStand(void)  // draw a grandstand
{
  const GLfloat standBrick[4]={.8,.3,.3, 1};
  glMaterialfv(GL_FRONT_AND_BACK, GL_AMBIENT_AND_DIFFUSE, standBrick);
  drawBricks();
  glEnable(GL_TEXTURE_2D);
  glBegin(GL_POLYGON);  //stand side
    glTexCoord2f(0.,0.); glVertex3f(-15, -47, .01);  // tall south bottom
    glTexCoord2f(0.,16.); glVertex3f(-15, -31.5, .01);  //sets texel coords
    glTexCoord2f(8.,0.); glVertex3f(-15, -47, 8);
  glEnd();
  glBegin(GL_POLYGON);  // stand side
    glTexCoord2f(0.,0.); glVertex3f(15, -47, .01);  //sets texel coords
    glTexCoord2f(0.,16.); glVertex3f(15, -31.5, .01);
    glTexCoord2f(8.,0.); glVertex3f(15, -47, 8);
  glEnd();
  glDisable(GL_TEXTURE_2D);

  glBegin(GL_POLYGON);  // draw back
    glVertex3f(15, -47, .01);
    glVertex3f(-15, -47, .01);
    glVertex3f(-15, -47, 8);
    glVertex3f(15, -47, 8);
  glEnd();
  glPushMatrix();
  for(int i= 0; i<14; i++) {  // draw 14 rows of seats
    const GLfloat standRiser[4]={.7,.1,.1, 1};
    glMaterialfv(GL_FRONT_AND_BACK, GL_AMBIENT_AND_DIFFUSE, standRiser);
    glBegin(GL_POLYGON);  // vertical stand riser
      glVertex3f(-15, -33, .01);
      glVertex3f(-15, -33, .5);
      glVertex3f(15, -33, .5);
      glVertex3f(15, -33, .01);
    glEnd();
    const GLfloat standBench[4]={.5,.1,.1, 1};
    glMaterialfv(GL_FRONT_AND_BACK, GL_AMBIENT_AND_DIFFUSE, standBench);
    glBegin(GL_POLYGON);   // hor. stand bench
      glVertex3f(-15, -33, .5);
      glVertex3f(-15, -34, .5);
      glVertex3f(15, -34, .5);
      glVertex3f(15, -33, .5);
    glEnd();
    glTranslatef(0, -1, .5);
  }
  glPopMatrix();
}

void drawCar(void)  // BONUS feature: attempt to draw car for 3rd person view
{   // car not drawn right, disappears when facing due north or south
  const GLfloat car[4]={.9,.1,.1, 1};
  glMaterialfv(GL_FRONT_AND_BACK, GL_AMBIENT_AND_DIFFUSE, car);
  glBegin(GL_POLYGON);
    glVertex3d(prpx+2*cos(direction-0.7853),prpy+2*sin(direction-0.7853),.1);
    glVertex3d(vrpx,vrpy,.1);
    glVertex3d(prpx-2*cos(direction+2.3561),prpy-2*sin(direction-2.3561),.1);
    glVertex3d(prpx+2*cos(direction-2.3561),prpy+2*sin(direction+2.3561),.1);
    glVertex3f(prpx-2*cos(direction+0.7853),prpy-2*sin(direction+0.7853),.1);   
  glEnd();
}

void resize(int width, int height) // facilitate smooth window resize
{
  glViewport(0,0,width,height);
  windowWidth = width; windowHeight = height;  
}

void myKeyboard(unsigned char key, int x, int y)
{
  switch(tolower(key))
  {
    case 'w':  // accelerate 
      if(speed<1) speed+= .111;
      break; 
    case 's':  // decelerate 
      if(speed>.1) speed-= .111;
      break;   // Allows user to turn while stopped for viewing
    case 'd':  // day and night
      toggleNight= !toggleNight;
      break;  
    case 'a':  // grandstand light 
      toggleLight0= !toggleLight0;
      break;
    case 'e':  // headlight 
      toggleLight1= !toggleLight1;
      break;  
    case 0x1B:  // Exit
      exit(0) ;
    default: break;
  }
}

void mouse(int button, int state, int x, int y) //allow start/pause, and 1st or 3rd person
{
  if(button == GLUT_MIDDLE_BUTTON && state == GLUT_DOWN)
    toggle1stPerson= !toggle1stPerson;  // toggle 1st or 3rd person
  if(button == GLUT_RIGHT_BUTTON && state == GLUT_DOWN) {
    if(speed>0)  // if moving, stop
      speed= 0;
    else  // if stopped, go
      speed=.333;
  }
}

void passiveMotion (int x, int y) 
{
  xMouse= x;  // save x-coord. for steering
}

void animate(void)
{
  if(speed>0) { // allows user to start and pause.
    prpx+= speed*cos(direction);  //move camera speed(distance) in direction
    prpy+= speed*sin(direction);
    // find change in direction based on x-coord of mouse
    dtheta= (xMouse- windowWidth/2)*0.0001;
    direction-=dtheta;
    if(direction> (2*PI)) direction-= (2*PI);
    if(direction<= 0) direction+= (2*PI);
    // set viewplane to 1 meter ahead of camera 
    vrpx= prpx+ 1*cos(direction);
    vrpy= prpy+ 1*sin(direction);
  }
}

void idle(void) { 
	/* determine how much time has elapsed since we re-drew the
		window, for animation *///Idle function from course notes.
  static clock_t old_t=0;
  float dt;
  clock_t t;
  t=clock();
  if (old_t == 0) {/* gets here the 1st time 'idle' is called. save the current time */
    old_t=t;
    return;
  }  // Calc how many seconds it's been since we re-drew the window. 
  // The operating system's clock ticks CLOCKS_PER_SEC times each second, so 
  // divide number of ticks by CLOCKS_PER_SEC to get seconds.
  dt=(float)(t-old_t)/CLOCKS_PER_SEC;
  /* if it's been over 1/30 second, redraw so we'll draw 30 fps for a smooth animation. */
  if (dt>0.033) { /* it's time to re-draw the window */
    animate();  // animate moves camera
    glutPostRedisplay();  /* cause OpenGL to call 'display' */
    dt=0; old_t=t;    // remember current time when this frame is drawn.
    return ;
  }
}

void display(void)
{
  glClear(GL_COLOR_BUFFER_BIT | GL_DEPTH_BUFFER_BIT);
///// Grandstand Light (LIGHT0) properties //////
  glLightfv(GL_LIGHT0,GL_POSITION, light0Posn);
  glLightfv(GL_LIGHT0,GL_DIFFUSE, light0Dif);
  glLightf(GL_LIGHT0,GL_CONSTANT_ATTENUATION, .2);
  glLightf(GL_LIGHT0,GL_LINEAR_ATTENUATION, .4);
  glLightf(GL_LIGHT0,GL_QUADRATIC_ATTENUATION, .5);

///// Toggle Lighting /////
  if(toggleNight == false) {  
    glLightModelfv(GL_LIGHT_MODEL_AMBIENT, day);  // daylight
    glClearColor(.527,.804,.976, 1);  // sky blue background color
  }
  else {
    glLightModelfv(GL_LIGHT_MODEL_AMBIENT, night);  // night
    glClearColor(0, .204,.376, 1);  // night blue background color
  }
  if(toggleLight0 == true) glEnable(GL_LIGHT0);  // toggle stand light
  else glDisable(GL_LIGHT0);
  if(toggleLight1 == true) glEnable(GL_LIGHT1);  // toggle headlight
  else glDisable(GL_LIGHT1);
  
  glMatrixMode(GL_MODELVIEW);
  glLoadIdentity();
  
///// toggle between overhead and camera view /////
  if(toggle1stPerson == false)
    glOrtho(-88,88,-66,66,-1,5);
  else {
    gluPerspective(viewAngle,aspRatio,nearz,farz);
    gluLookAt(prpx,prpy,prpz, vrpx,vrpy,vrpz, vupx,vupy,vupz); 
  } 

///// Headlight (LIGHT1) properties ///////
  light1Dir[0]=vrpx-prpx; light1Dir[1]=vrpy-prpy; light1Dir[2]=-.02; light1Dir[3]=1;
  light1Posn[0]=prpx+light1Dir[0]; light1Posn[1]=prpy+light1Dir[1];light1Posn[2]= 1.;

  glLightfv(GL_LIGHT1,GL_POSITION, light1Posn);
  glLightfv(GL_LIGHT1,GL_DIFFUSE, light1Dif);
  glLightfv(GL_LIGHT1,GL_SPOT_DIRECTION, light1Dir);
  glLightf(GL_LIGHT1,GL_SPOT_CUTOFF, 60);
  glLightf(GL_LIGHT1,GL_SPOT_EXPONENT, 3);
  glLightf(GL_LIGHT1,GL_CONSTANT_ATTENUATION, .5);
  glLightf(GL_LIGHT1,GL_LINEAR_ATTENUATION, 0.1);
  glLightf(GL_LIGHT1,GL_QUADRATIC_ATTENUATION, .1);
///// end lighting /////
// draw 2D objects //  
  drawGround();
  glPushMatrix(); 
  drawRect();
  drawStand();
  glTranslatef(0,-50,0);
  drawRect();
  glTranslatef(50,50,0);
  drawSemiCirc();
  glTranslatef(-100,0,0);
  glRotatef(180,0,0,1); 
  drawSemiCirc(); 
  glPopMatrix();
  drawFinishLine();
  drawCar();
  
  glutSwapBuffers();
  glFlush();
}

/************************ MAIN *************************/
int main(int argc, char **argv) 
{
  cout<<"Toby Hummel: Project 3"<<endl<<endl<<"INSTUCTIONS: \n"
    <<"Right-click to start/pause."<<endl
    <<"Middle-click to toggle 1st person or top-down view."<<endl
    <<"Move mouse left or right to steer in that direction."<<endl
    <<"Keys:"<<endl<<"W: accelerate"<<endl<<"S: decelerate"<<endl
    <<"A: toggle grandstand light"<<endl<<"D: toggle night or day"<<endl
    <<"E: toggle headlights"<<endl<<"Esc: exit"<<endl;
  
  glutInit(&argc, argv);
  glutInitDisplayMode(GLUT_DOUBLE | GLUT_RGB | GLUT_DEPTH);
  glutInitWindowPosition(75,75);
  glutInitWindowSize(windowWidth,windowHeight);
  glutCreateWindow("Toby Hummel: Project 3- Racetrack");
  glEnable(GL_DEPTH_TEST);  // enable hidden surf. removal
  glEnable(GL_LIGHTING);
  glEnable(GL_SMOOTH);
  glLightModeli(GL_LIGHT_MODEL_LOCAL_VIEWER, GL_TRUE);
  glPixelStorei(GL_UNPACK_ALIGNMENT,1);  // for Texture maps

  glMatrixMode(GL_PROJECTION);
  glLoadIdentity();
// register gl functions
  glutDisplayFunc(display);
  glutReshapeFunc(resize);
  glutMouseFunc(mouse);
  glutIdleFunc(idle);
  glutPassiveMotionFunc(passiveMotion);
  glutKeyboardFunc(myKeyboard);

  glutMainLoop();
  return 0;
}